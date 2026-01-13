<?php

declare(strict_types=1);

namespace Langfuse\Client\Services;

use Langfuse\Client\Configuration;
use Langfuse\Observability\Contracts\SpanInterface;
use Langfuse\Observability\Contracts\TracerInterface;
use Langfuse\Support\Enums\ObservationType;
use Langfuse\Support\Enums\SpanLevel;

/**
 * Service for high-level tracing operations
 */
class TracingService
{
    public function __construct(
        private readonly Configuration $config,
        private readonly TracerInterface $tracer,
        private readonly \Langfuse\Support\Contracts\IdGeneratorInterface $idGenerator,
    ) {
    }

    /**
     * Start a new span
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
        if (!$this->config->isTracingEnabled()) {
            return new \Langfuse\Observability\Spans\NullSpan($this->idGenerator);
        }

        if (!$this->config->shouldSample()) {
            return new \Langfuse\Observability\Spans\NullSpan($this->idGenerator);
        }

        return $this->tracer->startSpan(
            name: $name,
            type: $type,
            input: $input,
            output: $output,
            metadata: $metadata,
            version: $version,
            level: $level,
            statusMessage: $statusMessage,
            parentId: $parentId,
            model: $model,
        );
    }

    /**
     * Flush all pending spans
     */
    public function flush(): void
    {
        $this->tracer->flush();
    }

    /**
     * Shutdown the tracer
     */
    public function shutdown(): void
    {
        $this->tracer->shutdown();
    }
}
