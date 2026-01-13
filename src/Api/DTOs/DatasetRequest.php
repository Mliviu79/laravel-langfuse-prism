<?php

declare(strict_types=1);

namespace Langfuse\Api\DTOs;

/**
 * Data Transfer Object for dataset API requests
 */
readonly class DatasetRequest
{
    public function __construct(
        public string $name,
        public ?string $description = null,
        public array $metadata = [],
    ) {
    }

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            description: $data['description'] ?? null,
            metadata: $data['metadata'] ?? [],
        );
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'description' => $this->description,
            'metadata' => $this->metadata,
        ], fn ($value) => $value !== null && $value !== []);
    }
}
