<?php

declare(strict_types=1);

namespace Langfuse\Observability\Spans;

use DateTime;
use Langfuse\Observability\Concerns\HasSpanAttributes;
use Langfuse\Observability\Contracts\EventDispatcherInterface;
use Langfuse\Observability\Contracts\SpanInterface;
use Langfuse\Observability\Contracts\TracerInterface;
use Langfuse\Scoring\Enums\ScoreDataType;
use Langfuse\Scoring\Score;
use Langfuse\Support\Contracts\IdGeneratorInterface;
use Langfuse\Support\Enums\ObservationType;
use Langfuse\Support\Enums\SpanLevel;
use OpenTelemetry\API\Trace\SpanInterface as OtelSpanInterface;
use OpenTelemetry\Context\ScopeInterface;

/**
 * Wrapper around OpenTelemetry Span that implements Langfuse SpanInterface.
 *
 * Provides a high-level API for creating and managing spans with Langfuse-specific
 * attributes while delegating to OpenTelemetry for the underlying implementation.
 */
class OpenTelemetrySpan implements SpanInterface
{
    use HasSpanAttributes;

    public function __construct(
        private readonly OtelSpanInterface $otelSpan,
        private readonly string $spanId,
        private readonly string $traceId,
        private readonly string $name,
        private readonly ObservationType $type,
        private readonly IdGeneratorInterface $idGenerator,
        private readonly ?ScopeInterface $scope = null,
        private readonly ?TracerInterface $tracerWrapper = null,
        private readonly bool $isRootSpan = false,
        private readonly ?EventDispatcherInterface $eventDispatcher = null,
    ) {}

    public function getId(): string
    {
        return $this->spanId;
    }

    public function getTraceId(): string
    {
        return $this->traceId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): ObservationType
    {
        return $this->type;
    }

    /**
     * Get the underlying OpenTelemetry span
     */
    public function getOtelSpan(): OtelSpanInterface
    {
        return $this->otelSpan;
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
        if ($name !== null) {
            $this->otelSpan->updateName($name);
        }

        // Use trait methods for attribute setting
        $this->setInputAttributes($this->otelSpan, $input, $this->isRootSpan);
        $this->setOutputAttributes($this->otelSpan, $output, $this->isRootSpan);
        $this->setMetadataAttributes($this->otelSpan, $metadata);
        $this->setVersionAttribute($this->otelSpan, $version);
        $this->setLevelAttributes($this->otelSpan, $level, $statusMessage);
        $this->setStatusMessageAttribute($this->otelSpan, $statusMessage);
        $this->setModelAttributes($this->otelSpan, $model, $modelParameters);
        $this->setUsageAttributes($this->otelSpan, $usageDetails);
        $this->setCompletionStartTimeAttribute($this->otelSpan, $completionStartTime);
        $this->setCostAttributes($this->otelSpan, $costDetails);
        $this->setPromptAttributes($this->otelSpan, $promptName, $promptVersion);

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
        $this->setTraceAttributes(
            $this->otelSpan,
            $name,
            $userId,
            $sessionId,
            $version,
            $input,
            $output,
            $metadata,
            $tags,
            $public
        );

        return $this;
    }

    public function score(
        string $name,
        float|int|bool|string $value,
        ?string $scoreId = null,
        ?string $comment = null,
        ?string $configId = null,
    ): Score {
        $scoreId = $scoreId ?? $this->idGenerator->generateScoreId();

        // Add score as an event
        $this->otelSpan->addEvent('langfuse.score', [
            'score.id' => $scoreId,
            'score.name' => $name,
            'score.value' => is_bool($value) ? ($value ? 'true' : 'false') : (string) $value,
            'score.observation_id' => $this->spanId,
            'score.comment' => $comment,
            'score.config_id' => $configId,
        ]);

        $dataType = match (true) {
            is_bool($value) => ScoreDataType::BOOLEAN,
            is_numeric($value) => ScoreDataType::NUMERIC,
            default => ScoreDataType::CATEGORICAL,
        };

        return new Score(
            id: $scoreId,
            name: $name,
            value: $value,
            traceId: $this->traceId,
            observationId: $this->spanId,
            comment: $comment,
            configId: $configId,
            dataType: $dataType,
        );
    }

    public function scoreTrace(
        string $name,
        float|int|bool|string $value,
        ?string $scoreId = null,
        ?string $comment = null,
        ?string $configId = null,
    ): Score {
        $scoreId = $scoreId ?? $this->idGenerator->generateScoreId();

        // Add score as an event
        $this->otelSpan->addEvent('langfuse.trace.score', [
            'score.id' => $scoreId,
            'score.name' => $name,
            'score.value' => is_bool($value) ? ($value ? 'true' : 'false') : (string) $value,
            'score.trace_id' => $this->traceId,
            'score.comment' => $comment,
            'score.config_id' => $configId,
        ]);

        $dataType = match (true) {
            is_bool($value) => ScoreDataType::BOOLEAN,
            is_numeric($value) => ScoreDataType::NUMERIC,
            default => ScoreDataType::CATEGORICAL,
        };

        return new Score(
            id: $scoreId,
            name: $name,
            value: $value,
            traceId: $this->traceId,
            observationId: null,
            comment: $comment,
            configId: $configId,
            dataType: $dataType,
        );
    }

    /**
     * Start a child observation (span or generation) under this span.
     *
     * Since this span is already active in the OpenTelemetry context,
     * the child will automatically be parented to it.
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
    ): SpanInterface {
        // If we don't have a tracer wrapper reference, we can't create child spans
        if ($this->tracerWrapper === null) {
            return $this;
        }

        // Create child span - it will auto-parent from the current context
        // which is this span since we activated it
        $childSpan = $this->tracerWrapper->startSpan(
            name: $name,
            type: $type,
            input: $input,
            output: $output,
            metadata: $metadata,
            version: $version,
            level: $level,
            statusMessage: $statusMessage,
            model: $model,
        );

        // Apply additional parameters if provided
        if ($modelParameters !== null || $usageDetails !== null || $costDetails !== null || $completionStartTime !== null) {
            $childSpan->update(
                completionStartTime: $completionStartTime,
                modelParameters: $modelParameters,
                usageDetails: $usageDetails,
                costDetails: $costDetails,
            );
        }

        return $childSpan;
    }

    /**
     * End the span and detach from context.
     *
     * This restores the previous context, so subsequent spans
     * will no longer be parented to this span.
     */
    public function end(?DateTime $endTime = null): SpanInterface
    {
        if ($endTime !== null) {
            // OpenTelemetry doesn't support setting custom end time directly
            // Store it as an attribute instead
            $this->otelSpan->setAttribute('langfuse.observation.end_time', $endTime->format('c'));
        }

        // Detach the scope to restore previous context
        $this->scope?->detach();

        // End the OpenTelemetry span
        $this->otelSpan->end();

        // Remove from active spans
        $this->tracerWrapper?->removeSpan($this->spanId);

        // Dispatch span ended event
        $this->eventDispatcher?->dispatchSpanEnded($this);

        // If this is a root span, also dispatch trace completed
        if ($this->isRootSpan) {
            $this->eventDispatcher?->dispatchTraceCompleted($this->traceId);
        }

        return $this;
    }

    /**
     * Check if this is a root span.
     */
    public function isRootSpan(): bool
    {
        return $this->isRootSpan;
    }
}
