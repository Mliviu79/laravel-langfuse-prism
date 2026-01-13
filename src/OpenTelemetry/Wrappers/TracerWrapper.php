<?php

declare(strict_types=1);

namespace Langfuse\OpenTelemetry\Wrappers;

use Langfuse\Observability\Contracts\EventDispatcherInterface;
use Langfuse\Observability\Contracts\SpanInterface;
use Langfuse\Observability\Contracts\SpanManagerInterface;
use Langfuse\Observability\Contracts\TracerInterface;
use Langfuse\Observability\Services\AttributeMapperService;
use Langfuse\Observability\Services\ParentResolverService;
use Langfuse\Observability\Spans\OpenTelemetrySpan;
use Langfuse\Support\Contracts\IdGeneratorInterface;
use Langfuse\Support\Enums\ObservationType;
use Langfuse\Support\Enums\SpanLevel;
use OpenTelemetry\Context\Context;
use OpenTelemetry\SDK\Trace\TracerProvider;

/**
 * Wrapper around OpenTelemetry tracer that provides Langfuse-specific functionality
 */
class TracerWrapper implements TracerInterface
{
    public function __construct(
        private readonly TracerProvider $tracerProvider,
        private readonly SpanManagerInterface $spanManager,
        private readonly ParentResolverService $parentResolver,
        private readonly AttributeMapperService $attributeMapper,
        private readonly IdGeneratorInterface $idGenerator,
        private readonly ?EventDispatcherInterface $eventDispatcher = null,
        private readonly string $instrumentationName = 'langfuse-php',
        private readonly string $instrumentationVersion = '1.0.0'
    ) {
    }

    /**
     * Get the event dispatcher if one is set.
     */
    public function getEventDispatcher(): ?EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }

    /**
     * Create a new span with Langfuse-specific attributes.
     *
     * The span is automatically activated in the OpenTelemetry context,
     * so any child spans created during this span's lifetime will automatically
     * be parented to it (unless an explicit parentId is provided).
     */
    public function startSpan(
        string $name,
        ObservationType $type = ObservationType::SPAN,
        mixed $input = null,
        mixed $output = null,
        ?array $metadata = null,
        ?string $version = null,
        ?SpanLevel $level = null,
        ?string $statusMessage = null,
        ?string $parentId = null,
        ?string $model = null,
    ): SpanInterface {
        $tracer = $this->tracerProvider->getTracer(
            $this->instrumentationName,
            $this->instrumentationVersion
        );

        $spanKind = $this->attributeMapper->mapObservationTypeToSpanKind($type);

        // Start the OpenTelemetry span
        $spanBuilder = $tracer->spanBuilder($name)
            ->setSpanKind($spanKind);

        // Determine parent context
        $currentContext = Context::getCurrent();
        $parentInfo = $this->parentResolver->resolveParent($parentId, $currentContext);

        $parentObservationId = $parentInfo['parentObservationId'];
        $isRootSpan = $parentInfo['isLangfuseRoot'];
        $otelParentContext = $parentInfo['otelParentContext'];

        if ($otelParentContext !== null) {
            $spanBuilder->setParent($otelParentContext);
        }

        $otelSpan = $spanBuilder->startSpan();
        $spanId = $this->idGenerator->generateObservationId();
        $traceId = $otelSpan->getContext()->getTraceId();

        // Activate the span in the current context so child spans inherit it
        $newContext = $otelSpan->storeInContext(Context::getCurrent());
        $scope = $newContext->activate();

        // Set Langfuse-specific attributes
        $otelSpan->setAttribute('langfuse.observation.type', $type->value);
        $otelSpan->setAttribute('langfuse.observation.id', $spanId);

        // Mark span for Langfuse export (used by OTEL Collector filter)
        // Must be a string 'true' for proper filtering
        $otelSpan->setAttribute('langfuse.export', 'true');

        // Set parent observation ID if this is a child span
        if ($parentObservationId !== null) {
            $otelSpan->setAttribute('langfuse.observation.parent_id', $parentObservationId);
        }

        // If this is a root span, auto-populate trace-level attributes
        if ($isRootSpan) {
            $otelSpan->setAttribute('langfuse.trace.name', $name);
            if ($input !== null) {
                $otelSpan->setAttribute('langfuse.trace.input', json_encode($input));
            }
        }

        if ($input !== null) {
            $inputJson = json_encode($input);
            // Set Langfuse-specific attribute as JSON
            $otelSpan->setAttribute('langfuse.observation.input', $inputJson);

            // input.value should be a simple string, not JSON (per Langfuse OTEL docs)
            // If input is not a string, use the JSON representation
            $inputString = is_string($input) ? $input : $inputJson;
            $otelSpan->setAttribute('input.value', $inputString);
        }

        if ($output !== null) {
            $outputJson = json_encode($output);
            // Set Langfuse-specific attribute as JSON
            $otelSpan->setAttribute('langfuse.observation.output', $outputJson);

            // output.value should be a simple string, not JSON (per Langfuse OTEL docs)
            // If output is not a string, use the JSON representation
            $outputString = is_string($output) ? $output : $outputJson;
            $otelSpan->setAttribute('output.value', $outputString);

            // If root span, also set trace output
            if ($isRootSpan) {
                $otelSpan->setAttribute('langfuse.trace.output', $outputJson);
            }
        }

        if ($metadata !== null) {
            foreach ($metadata as $key => $value) {
                $otelSpan->setAttribute("langfuse.observation.metadata.{$key}", is_scalar($value) ? $value : json_encode($value));
            }
        }

        if ($version !== null) {
            $otelSpan->setAttribute('langfuse.observation.version', $version);
        }

        if ($level !== null) {
            $otelSpan->setAttribute('langfuse.observation.level', $level->value);
        }

        if ($statusMessage !== null) {
            $otelSpan->setAttribute('langfuse.observation.status_message', $statusMessage);
        }

        if ($model !== null) {
            $otelSpan->setAttribute('gen_ai.request.model', $model);
            $otelSpan->setAttribute('langfuse.observation.model.name', $model);
        }

        // Create wrapper span with scope for later detachment
        $span = new OpenTelemetrySpan(
            otelSpan: $otelSpan,
            spanId: $spanId,
            traceId: $traceId,
            name: $name,
            type: $type,
            idGenerator: $this->idGenerator,
            scope: $scope,
            tracerWrapper: $this,
            isRootSpan: $isRootSpan,
            eventDispatcher: $this->eventDispatcher,
        );

        // Store in active spans
        $this->spanManager->registerSpan($spanId, $span);

        // Dispatch span started event
        $this->eventDispatcher?->dispatchSpanStarted($span);

        return $span;
    }

    /**
     * Get an active span by ID
     */
    public function getSpan(string $spanId): ?SpanInterface
    {
        return $this->spanManager->getSpan($spanId);
    }

    /**
     * Remove span from active spans (called when span ends)
     */
    public function removeSpan(string $spanId): void
    {
        $this->spanManager->removeSpan($spanId);
    }

    /**
     * Flush all pending spans
     */
    public function flush(): void
    {
        $this->tracerProvider->forceFlush();
    }

    /**
     * Shutdown the tracer provider
     */
    public function shutdown(): void
    {
        $this->tracerProvider->shutdown();
    }
}
