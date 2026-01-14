<?php

declare(strict_types=1);

namespace Langfuse\Observability\Contracts;

use DateTime;
use Langfuse\Scoring\Score;
use Langfuse\Support\Enums\ObservationType;
use Langfuse\Support\Enums\SpanLevel;

interface SpanInterface
{
    /**
     * Get the span ID
     */
    public function getId(): string;

    /**
     * Get the trace ID this span belongs to
     */
    public function getTraceId(): string;

    /**
     * Get the span name
     */
    public function getName(): string;

    /**
     * Get the span type
     */
    public function getType(): ObservationType;

    /**
     * Update the span with new information
     */
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
    ): self;

    /**
     * Update the trace this span belongs to
     */
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
    ): self;

    /**
     * Create a score for this span
     */
    public function score(
        string $name,
        float|int|bool|string $value,
        ?string $scoreId = null,
        ?string $comment = null,
        ?string $configId = null,
    ): Score;

    /**
     * Create a score for the trace this span belongs to
     */
    public function scoreTrace(
        string $name,
        float|int|bool|string $value,
        ?string $scoreId = null,
        ?string $comment = null,
        ?string $configId = null,
    ): Score;

    /**
     * Start a child observation
     */
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
    ): self;

    /**
     * End the span
     */
    public function end(?DateTime $endTime = null): self;
}
