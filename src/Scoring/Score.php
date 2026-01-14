<?php

declare(strict_types=1);

namespace Langfuse\Scoring;

use Langfuse\Scoring\Enums\ScoreDataType;

final readonly class Score
{
    public function __construct(
        public string $id,
        public string $name,
        public float|int|bool|string $value,
        public string $traceId,
        public ?string $observationId = null,
        public ?string $comment = null,
        public ?string $configId = null,
        public ?ScoreDataType $dataType = null,
    ) {}

    /**
     * Get the score data type based on the value
     */
    public function getDataType(): ScoreDataType
    {
        if ($this->dataType !== null) {
            return $this->dataType;
        }

        return match (true) {
            is_bool($this->value) => ScoreDataType::BOOLEAN,
            is_numeric($this->value) => ScoreDataType::NUMERIC,
            default => ScoreDataType::CATEGORICAL,
        };
    }

    /**
     * Check if this is a trace-level score
     */
    public function isTraceScore(): bool
    {
        return $this->observationId === null;
    }

    /**
     * Check if this is an observation-level score
     */
    public function isObservationScore(): bool
    {
        return $this->observationId !== null;
    }

    /**
     * Convert to array representation
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'value' => $this->value,
            'traceId' => $this->traceId,
            'observationId' => $this->observationId,
            'comment' => $this->comment,
            'configId' => $this->configId,
            'dataType' => $this->getDataType()->value,
        ];
    }
}
