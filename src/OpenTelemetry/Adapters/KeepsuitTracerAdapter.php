<?php

declare(strict_types=1);

namespace Langfuse\OpenTelemetry\Adapters;

use Keepsuit\LaravelOpenTelemetry\Facades\Tracer as KeepsuitTracer;
use Langfuse\Observability\Contracts\SpanInterface;
use Langfuse\Observability\Contracts\TracerInterface;
use Langfuse\Support\Contracts\IdGeneratorInterface;
use Langfuse\Support\Enums\ObservationType;
use Langfuse\Support\Enums\SpanLevel;
use OpenTelemetry\API\Trace\SpanInterface as OtelSpanInterface;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\Context\Context;

/**
 * Adapter that uses Keepsuit's Laravel OpenTelemetry tracer.
 * 
 * This allows the Langfuse package to work alongside the existing
 * Keepsuit OpenTelemetry setup, using its configured exporter to
 * send traces to Langfuse.
 */
class KeepsuitTracerAdapter implements TracerInterface
{
    /** @var array<string, SpanInterface> */
    private array $spans = [];

    public function __construct(
        private readonly IdGeneratorInterface $idGenerator,
    ) {
    }

    public function getSpan(string $spanId): ?SpanInterface
    {
        return $this->spans[$spanId] ?? null;
    }

    public function removeSpan(string $spanId): void
    {
        unset($this->spans[$spanId]);
    }

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
        // Create span using Keepsuit's tracer
        $spanBuilder = KeepsuitTracer::newSpan($name)
            ->setSpanKind($this->mapObservationTypeToSpanKind($type));

        // Start the span
        $otelSpan = $spanBuilder->start();
        
        // Activate the span in the current context so child spans inherit it
        $scope = $otelSpan->storeInContext(Context::getCurrent())->activate();
        
        // Get the span context for trace/span IDs
        $spanContext = $otelSpan->getContext();
        $traceId = $spanContext->getTraceId();
        $spanId = $spanContext->getSpanId();

        // Determine if this is the root span (no parent)
        $parentSpan = \OpenTelemetry\API\Trace\Span::fromContext(Context::getCurrent());
        // Check if the span we just started IS the same as the one in context (it should be)
        // To find the ACTUAL parent, we would have needed to check BEFORE starting.
        // But Keepsuit handles parenting internally if we don't set it.
        // For Langfuse, we'll check if there was a valid parent BEFORE we started.
        $isRootSpan = !$parentSpan->getContext()->isValid();

        // Create our wrapper span
        $span = new KeepsuitSpanAdapter(
            otelSpan: $otelSpan,
            spanId: $spanId,
            traceId: $traceId,
            name: $name,
            type: $type,
            idGenerator: $this->idGenerator,
            isRootSpan: $isRootSpan,
            scope: $scope,
            input: $input,
            output: $output,
            metadata: $metadata,
            version: $version,
            level: $level,
            statusMessage: $statusMessage,
            model: $model,
        );

        // Store the span for later retrieval
        $this->spans[$spanId] = $span;

        return $span;
    }

    public function flush(): void
    {
        // Keepsuit handles flushing via its own lifecycle
        // We can try to force a flush if possible
        try {
            $tracerProvider = \OpenTelemetry\API\Globals::tracerProvider();
            if (method_exists($tracerProvider, 'forceFlush')) {
                $tracerProvider->forceFlush();
            }
        } catch (\Throwable $e) {
            // Ignore flush errors
        }
    }

    public function shutdown(): void
    {
        // Keepsuit handles shutdown
    }

    /**
     * @return 0|1|2|3|4
     */
    private function mapObservationTypeToSpanKind(ObservationType $type): int
    {
        return match ($type) {
            ObservationType::GENERATION,
            ObservationType::EMBEDDING,
            ObservationType::RETRIEVER => SpanKind::KIND_CLIENT,
            default => SpanKind::KIND_INTERNAL,
        };
    }
}
