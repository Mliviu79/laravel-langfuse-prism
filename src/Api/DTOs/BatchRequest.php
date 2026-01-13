<?php

declare(strict_types=1);

namespace Langfuse\Api\DTOs;

/**
 * Data Transfer Object for batch API requests
 */
readonly class BatchRequest
{
    /**
     * @param array<int, array{id: string, timestamp: string, type: string, body: array}> $batch
     */
    public function __construct(
        public array $batch,
    ) {
    }

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            batch: $data['batch'] ?? [],
        );
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'batch' => $this->batch,
        ];
    }
}
