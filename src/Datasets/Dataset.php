<?php

declare(strict_types=1);

namespace Langfuse\Datasets;

use Langfuse\Datasets\Enums\DatasetStatus;

final readonly class Dataset
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $description = null,
        public array $metadata = [],
        public DatasetStatus $status = DatasetStatus::ACTIVE,
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
            'metadata' => $this->metadata,
            'status' => $this->status->value,
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
            metadata: $data['metadata'] ?? [],
            status: DatasetStatus::from($data['status'] ?? 'ACTIVE'),
            createdAt: isset($data['createdAt']) ? new \DateTime($data['createdAt']) : null,
            updatedAt: isset($data['updatedAt']) ? new \DateTime($data['updatedAt']) : null,
        );
    }
}
