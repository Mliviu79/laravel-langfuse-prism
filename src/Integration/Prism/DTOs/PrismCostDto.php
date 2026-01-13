<?php

declare(strict_types=1);

namespace Langfuse\Integration\Prism\DTOs;

/**
 * Data Transfer Object for Prism cost information
 */
readonly class PrismCostDto
{
    public function __construct(
        public float $inputCost = 0.0,
        public float $outputCost = 0.0,
        public float $totalCost = 0.0,
    ) {
    }

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            inputCost: $data['inputCost'] ?? $data['input'] ?? 0.0,
            outputCost: $data['outputCost'] ?? $data['output'] ?? 0.0,
            totalCost: $data['totalCost'] ?? $data['total'] ?? 0.0,
        );
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'inputCost' => $this->inputCost,
            'outputCost' => $this->outputCost,
            'totalCost' => $this->totalCost,
        ];
    }
}
