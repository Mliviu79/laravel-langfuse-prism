<?php

declare(strict_types=1);

namespace Langfuse\Observability\Spans;

use Langfuse\Observability\Contracts\SpanInterface;
use Langfuse\Observability\Contracts\TracerInterface;
use Langfuse\Support\Contracts\IdGeneratorInterface;
use Langfuse\Support\Enums\ObservationType;
use Langfuse\Support\Enums\SpanLevel;

/**
 * Null tracer implementation for when tracing is disabled
 */
class NullTracer implements TracerInterface
{
    public function __construct(
        private readonly ?IdGeneratorInterface $idGenerator = null
    ) {
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
        return new NullSpan($this->idGenerator);
    }

    public function getSpan(string $spanId): ?SpanInterface
    {
        return null;
    }

    public function removeSpan(string $spanId): void
    {
        // No-op
    }

    public function flush(): void
    {
        // No-op
    }

    public function shutdown(): void
    {
        // No-op
    }
}
