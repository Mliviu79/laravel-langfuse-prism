<?php

declare(strict_types=1);

namespace Langfuse\Observability\Spans;

use DateTime;
use Illuminate\Support\Str;
use Langfuse\Observability\Contracts\SpanInterface;
use Langfuse\Scoring\Score;
use Langfuse\Support\Contracts\IdGeneratorInterface;
use Langfuse\Support\Enums\ObservationType;
use Langfuse\Support\Enums\SpanLevel;

/**
 * Null object pattern for when tracing is disabled
 */
class NullSpan implements SpanInterface
{
    public function __construct(
        private readonly ?IdGeneratorInterface $idGenerator = null
    ) {
    }

    /**
     * Generate a fallback score ID when no IdGenerator is available
     */
    private function generateFallbackScoreId(): string
    {
        return $this->idGenerator?->generateScoreId() ?? (string) Str::uuid();
    }

    public function getId(): string
    {
        return 'null';
    }

    public function getTraceId(): string
    {
        return 'null';
    }

    public function getName(): string
    {
        return 'null';
    }

    public function getType(): ObservationType
    {
        return ObservationType::SPAN;
    }

    public function update(
        ?string $name = null,
        mixed $input = null,
        mixed $output = null,
        ?array $metadata = null,
        ?string $version = null,
        ?SpanLevel $level = null,
        ?string $statusMessage = null,
        ?DateTime $completionStartTime = null,
        ?string $model = null,
        ?array $modelParameters = null,
        ?array $usageDetails = null,
        ?array $costDetails = null,
        ?string $promptName = null,
        ?int $promptVersion = null,
    ): SpanInterface {
        return $this;
    }

    public function updateTrace(
        ?string $name = null,
        ?string $userId = null,
        ?string $sessionId = null,
        ?string $version = null,
        mixed $input = null,
        mixed $output = null,
        ?array $metadata = null,
        ?array $tags = null,
        ?bool $public = null,
    ): SpanInterface {
        return $this;
    }

    public function score(
        string $name,
        float|int|bool|string $value,
        ?string $scoreId = null,
        ?string $comment = null,
        ?string $configId = null,
    ): Score {
        return new Score(
            id: $scoreId ?? $this->generateFallbackScoreId(),
            name: $name,
            value: $value,
            traceId: 'null',
        );
    }

    public function scoreTrace(
        string $name,
        float|int|bool|string $value,
        ?string $scoreId = null,
        ?string $comment = null,
        ?string $configId = null,
    ): Score {
        return new Score(
            id: $scoreId ?? $this->generateFallbackScoreId(),
            name: $name,
            value: $value,
            traceId: 'null',
        );
    }

    public function startObservation(
        string $name,
        ObservationType $type,
        mixed $input = null,
        mixed $output = null,
        ?array $metadata = null,
        ?string $version = null,
        ?SpanLevel $level = null,
        ?string $statusMessage = null,
        ?DateTime $completionStartTime = null,
        ?string $model = null,
        ?array $modelParameters = null,
        ?array $usageDetails = null,
        ?array $costDetails = null,
    ): SpanInterface {
        return $this;
    }

    public function end(?DateTime $endTime = null): SpanInterface
    {
        return $this;
    }
}
