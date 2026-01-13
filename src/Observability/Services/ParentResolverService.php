<?php

declare(strict_types=1);

namespace Langfuse\Observability\Services;

use Langfuse\Observability\Contracts\SpanInterface;
use Langfuse\Observability\Contracts\SpanManagerInterface;
use OpenTelemetry\API\Trace\Span;
use OpenTelemetry\API\Trace\SpanInterface as OtelSpanInterface;
use OpenTelemetry\Context\Context;

/**
 * Service for resolving parent spans
 */
class ParentResolverService
{
    public function __construct(
        private readonly SpanManagerInterface $spanManager
    ) {
    }

    /**
     * Resolve parent span information
     *
     * @param string|null $parentId Explicit parent ID
     * @param Context $currentContext Current OpenTelemetry context
     * @return array{parentObservationId: string|null, isLangfuseRoot: bool, otelParentContext: Context|null}
     */
    public function resolveParent(?string $parentId, Context $currentContext): array
    {
        $parentObservationId = null;
        $isLangfuseRoot = true;
        $otelParentContext = null;

        // Priority 1: Explicit parentId provided (Langfuse span)
        if ($parentId !== null && $this->spanManager->hasSpan($parentId)) {
            $parentSpan = $this->spanManager->getSpan($parentId);
            if ($parentSpan !== null) {
                $parentObservationId = $parentSpan->getId();
                // Get OTel span from parent if it's an OpenTelemetrySpan
                if (method_exists($parentSpan, 'getOtelSpan')) {
                    /** @var OtelSpanInterface $otelSpan */
                    $otelSpan = $parentSpan->getOtelSpan();
                    $otelParentContext = $otelSpan->storeInContext($currentContext);
                }
                $isLangfuseRoot = false;
            }
        } else {
            // Priority 2: Auto-detect parent from current OpenTelemetry context
            $currentSpan = Span::fromContext($currentContext);
            if ($currentSpan->getContext()->isValid()) {
                $otelParentContext = $currentContext;
                
                // Check if this OTel parent is a Langfuse span (in our span manager)
                $parentTraceId = $currentSpan->getContext()->getTraceId();
                $parentSpanId = $currentSpan->getContext()->getSpanId();
                
                foreach ($this->spanManager->getAllSpans() as $activeSpan) {
                    if (method_exists($activeSpan, 'getOtelSpan')) {
                        /** @var OtelSpanInterface $activeOtelSpan */
                        $activeOtelSpan = $activeSpan->getOtelSpan();
                        $activeOtelContext = $activeOtelSpan->getContext();
                        
                        if ($activeOtelContext->getTraceId() === $parentTraceId 
                            && $activeOtelContext->getSpanId() === $parentSpanId) {
                            $parentObservationId = $activeSpan->getId();
                            $isLangfuseRoot = false;
                            break;
                        }
                    }
                }
                // If we didn't find the parent in span manager, this is still the Langfuse root
                // (parent is from auto-instrumentation, not a Langfuse span)
            }
        }

        return [
            'parentObservationId' => $parentObservationId,
            'isLangfuseRoot' => $isLangfuseRoot,
            'otelParentContext' => $otelParentContext,
        ];
    }
}
