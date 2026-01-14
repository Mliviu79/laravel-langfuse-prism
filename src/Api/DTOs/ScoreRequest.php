<?php

declare(strict_types=1);

namespace Langfuse\Api\DTOs;

/**
 * Data Transfer Object for score API requests
 */
readonly class ScoreRequest
{
    public function __construct(
        public string $id,
        public string $name,
        public float|int|bool|string $value,
        public string $dataType,
        public ?string $traceId = null,
        public ?string $observationId = null,
        public ?string $comment = null,
        public ?string $configId = null,
    ) {}

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
            value: $data['value'],
            dataType: $data['dataType'],
            traceId: $data['traceId'] ?? null,
            observationId: $data['observationId'] ?? null,
            comment: $data['comment'] ?? null,
            configId: $data['configId'] ?? null,
        );
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'name' => $this->name,
            'value' => $this->value,
            'dataType' => $this->dataType,
            'traceId' => $this->traceId,
            'observationId' => $this->observationId,
            'comment' => $this->comment,
            'configId' => $this->configId,
        ], fn ($value) => $value !== null);
    }
}
