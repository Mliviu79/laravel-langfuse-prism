<?php

declare(strict_types=1);

namespace Langfuse\Observability\Contracts;

use Langfuse\Support\Enums\ObservationType;
use Langfuse\Support\Enums\SpanLevel;

/**
 * Interface for tracer implementations
 */
interface TracerInterface
{
    /**
     * Start a new span
     *
     * @param string $name Span name
     * @param ObservationType $type Observation type
     * @param mixed $input Input data
     * @param mixed $output Output data
     * @param array|null $metadata Metadata
     * @param string|null $version Version
     * @param SpanLevel|null $level Span level
     * @param string|null $statusMessage Status message
     * @param string|null $parentId Parent span ID
     * @param string|null $model Model name
     * @return SpanInterface Created span
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
    ): SpanInterface;

    /**
     * Get an active span by ID
     */
    public function getSpan(string $spanId): ?SpanInterface;

    /**
     * Remove span from active spans
     */
    public function removeSpan(string $spanId): void;

    /**
     * Flush all pending spans
     */
    public function flush(): void;

    /**
     * Shutdown the tracer
     */
    public function shutdown(): void;
}
