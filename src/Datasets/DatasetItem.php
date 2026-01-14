<?php

declare(strict_types=1);

namespace Langfuse\Datasets;

final readonly class DatasetItem
{
    public function __construct(
        public string $id,
        public string $datasetId,
        public string $datasetName,
        public mixed $input,
        public mixed $expectedOutput = null,
        public array $metadata = [],
        public ?string $sourceTraceId = null,
        public ?string $sourceObservationId = null,
        public ?\DateTime $createdAt = null,
        public ?\DateTime $updatedAt = null,
    ) {}

    /**
     * Convert to array representation
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'datasetId' => $this->datasetId,
            'datasetName' => $this->datasetName,
            'input' => $this->input,
            'expectedOutput' => $this->expectedOutput,
            'metadata' => $this->metadata,
            'sourceTraceId' => $this->sourceTraceId,
            'sourceObservationId' => $this->sourceObservationId,
            'createdAt' => $this->createdAt?->format('c'),
            'updatedAt' => $this->updatedAt?->format('c'),
        ];
    }

    /**
     * Create from API response
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            datasetId: $data['datasetId'],
            datasetName: $data['datasetName'],
            input: $data['input'],
            expectedOutput: $data['expectedOutput'] ?? null,
            metadata: $data['metadata'] ?? [],
            sourceTraceId: $data['sourceTraceId'] ?? null,
            sourceObservationId: $data['sourceObservationId'] ?? null,
            createdAt: isset($data['createdAt']) ? new \DateTime($data['createdAt']) : null,
            updatedAt: isset($data['updatedAt']) ? new \DateTime($data['updatedAt']) : null,
        );
    }
}
