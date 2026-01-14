<?php

declare(strict_types=1);

namespace Langfuse\Datasets;

final readonly class DatasetRun
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $description = null,
        public ?string $datasetId = null,
        public ?string $datasetName = null,
        public array $metadata = [],
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
            'name' => $this->name,
            'description' => $this->description,
            'datasetId' => $this->datasetId,
            'datasetName' => $this->datasetName,
            'metadata' => $this->metadata,
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
            name: $data['name'],
            description: $data['description'] ?? null,
            datasetId: $data['datasetId'] ?? null,
            datasetName: $data['datasetName'] ?? null,
            metadata: $data['metadata'] ?? [],
            createdAt: isset($data['createdAt']) ? new \DateTime($data['createdAt']) : null,
            updatedAt: isset($data['updatedAt']) ? new \DateTime($data['updatedAt']) : null,
        );
    }
}
