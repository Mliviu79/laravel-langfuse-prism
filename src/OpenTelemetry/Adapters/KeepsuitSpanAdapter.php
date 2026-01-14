<?php

declare(strict_types=1);

namespace Langfuse\OpenTelemetry\Adapters;

use Langfuse\Observability\Concerns\HasSpanAttributes;
use Langfuse\Observability\Contracts\SpanInterface;
use Langfuse\Scoring\Score;
use Langfuse\Support\Contracts\IdGeneratorInterface;
use Langfuse\Support\Enums\ObservationType;
use Langfuse\Support\Enums\SpanLevel;
use OpenTelemetry\API\Trace\SpanInterface as OtelSpanInterface;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\Context\ScopeInterface;

/**
 * Adapter that wraps a Keepsuit/OpenTelemetry span as a Langfuse SpanInterface.
 */
class KeepsuitSpanAdapter implements SpanInterface
{
    use HasSpanAttributes;

    public function __construct(
        private readonly OtelSpanInterface $otelSpan,
        private readonly string $spanId,
        private readonly string $traceId,
        private readonly string $name,
        private readonly ObservationType $type,
        private readonly IdGeneratorInterface $idGenerator,
        private readonly bool $isRootSpan = false,
        private readonly ?ScopeInterface $scope = null,
        mixed $input = null,
        mixed $output = null,
        ?array $metadata = null,
        ?string $version = null,
        ?SpanLevel $level = null,
        ?string $statusMessage = null,
        ?string $model = null,
    ) {
        // Set initial Langfuse-specific attributes
        $this->setObservationTypeAttributes($this->otelSpan, $type, $this->spanId);

        if ($input !== null) {
            $this->setInputAttributes($this->otelSpan, $input, $this->isRootSpan);
        }
        if ($output !== null) {
            $this->setOutputAttributes($this->otelSpan, $output, $this->isRootSpan);
        }
        if ($metadata !== null) {
            $this->setMetadataAttributes($this->otelSpan, $metadata);
        }
        if ($version !== null) {
            $this->setVersionAttribute($this->otelSpan, $version);
        }
        if ($level !== null) {
            $this->setLevelAttributes($this->otelSpan, $level, $statusMessage);
        }
        if ($statusMessage !== null && $level === null) {
            $this->setStatusMessageAttribute($this->otelSpan, $statusMessage);
        }
        if ($model !== null) {
            $this->setModelAttributes($this->otelSpan, $model);
        }
    }

    public function getId(): string
    {
        return $this->spanId;
    }

    public function getSpanId(): string
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

    public function isRootSpan(): bool
    {
        return $this->isRootSpan;
    }

    public function update(
        ?string $name = null,
        mixed $input = null,
        mixed $output = null,
        ?array $metadata = null,
        ?string $version = null,
        ?SpanLevel $level = null,
        ?string $statusMessage = null,
        ?\DateTime $completionStartTime = null,
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
        if ($input !== null) {
            $this->setInputAttributes($this->otelSpan, $input, $this->isRootSpan);
        }
        if ($output !== null) {
            $this->setOutputAttributes($this->otelSpan, $output, $this->isRootSpan);
        }
        if ($metadata !== null) {
            $this->setMetadataAttributes($this->otelSpan, $metadata);
        }
        if ($version !== null) {
            $this->setVersionAttribute($this->otelSpan, $version);
        }
        if ($level !== null) {
            $this->setLevelAttributes($this->otelSpan, $level, $statusMessage);
        } elseif ($statusMessage !== null) {
            $this->setStatusMessageAttribute($this->otelSpan, $statusMessage);
        }
        if ($completionStartTime !== null) {
            $this->setCompletionStartTimeAttribute($this->otelSpan, $completionStartTime);
        }
        if ($model !== null || $modelParameters !== null) {
            $this->setModelAttributes($this->otelSpan, $model, $modelParameters);
        }
        if ($usageDetails !== null) {
            $this->setUsageAttributes($this->otelSpan, $usageDetails);
        }
        if ($costDetails !== null) {
            $this->setCostAttributes($this->otelSpan, $costDetails);
        }
        if ($promptName !== null || $promptVersion !== null) {
            $this->setPromptAttributes($this->otelSpan, $promptName, $promptVersion);
        }

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
        // Set trace-level attributes using the trait method
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

    public function end(?\DateTime $endTime = null): SpanInterface
    {
        if ($this->scope !== null) {
            $this->scope->detach();
        }

        $this->otelSpan->end();

        return $this;
    }

    public function recordException(\Throwable $exception): SpanInterface
    {
        $this->otelSpan->recordException($exception);
        $this->otelSpan->setStatus(StatusCode::STATUS_ERROR, $exception->getMessage());
        $this->setLevelAttributes($this->otelSpan, SpanLevel::ERROR, $exception->getMessage());

        return $this;
    }

    public function score(
        string $name,
        float|int|bool|string $value,
        ?string $scoreId = null,
        ?string $comment = null,
        ?string $configId = null,
    ): Score {
        $id = $scoreId ?? $this->idGenerator->generateScoreId();

        // Store score as span event
        $this->otelSpan->addEvent('langfuse.score', [
            'score.id' => $id,
            'score.name' => $name,
            'score.value' => is_bool($value) ? ($value ? 1 : 0) : $value,
            'score.trace_id' => $this->traceId,
            'score.observation_id' => $this->spanId,
            'score.comment' => $comment,
            'score.config_id' => $configId,
        ]);

        return new Score(
            id: $id,
            name: $name,
            value: $value,
            traceId: $this->traceId,
            observationId: $this->spanId,
            comment: $comment,
            configId: $configId,
        );
    }

    public function scoreTrace(
        string $name,
        float|int|bool|string $value,
        ?string $scoreId = null,
        ?string $comment = null,
        ?string $configId = null,
    ): Score {
        $id = $scoreId ?? $this->idGenerator->generateScoreId();

        // Store trace-level score as span event
        $this->otelSpan->addEvent('langfuse.trace_score', [
            'score.id' => $id,
            'score.name' => $name,
            'score.value' => is_bool($value) ? ($value ? 1 : 0) : $value,
            'score.trace_id' => $this->traceId,
            'score.config_id' => $configId,
        ]);

        return new Score(
            id: $id,
            name: $name,
            value: $value,
            traceId: $this->traceId,
            comment: $comment,
            configId: $configId,
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
        ?\DateTime $completionStartTime = null,
        ?string $model = null,
        ?array $modelParameters = null,
        ?array $usageDetails = null,
        ?array $costDetails = null,
    ): SpanInterface {
        // Create a child span using Keepsuit tracer - it will auto-parent from the active context
        $childOtelSpan = \Keepsuit\LaravelOpenTelemetry\Facades\Tracer::newSpan($name)
            ->setSpanKind($this->mapObservationTypeToSpanKind($type))
            ->start();

        // Activate the child span
        $childScope = $childOtelSpan->storeInContext(\OpenTelemetry\Context\Context::getCurrent())->activate();

        $childContext = $childOtelSpan->getContext();

        return new self(
            otelSpan: $childOtelSpan,
            spanId: $childContext->getSpanId(),
            traceId: $childContext->getTraceId(),
            name: $name,
            type: $type,
            idGenerator: $this->idGenerator,
            isRootSpan: false,
            scope: $childScope,
            input: $input,
            output: $output,
            metadata: $metadata,
            version: $version,
            level: $level,
            statusMessage: $statusMessage,
            model: $model,
        );
    }

    /**
     * @return 0|1|2|3|4
     */
    private function mapObservationTypeToSpanKind(ObservationType $type): int
    {
        return match ($type) {
            ObservationType::GENERATION,
            ObservationType::EMBEDDING,
            ObservationType::RETRIEVER => \OpenTelemetry\API\Trace\SpanKind::KIND_CLIENT,
            default => \OpenTelemetry\API\Trace\SpanKind::KIND_INTERNAL,
        };
    }
}
