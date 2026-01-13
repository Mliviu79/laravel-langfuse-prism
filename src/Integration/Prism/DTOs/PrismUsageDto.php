<?php

declare(strict_types=1);

namespace Langfuse\Integration\Prism\DTOs;

/**
 * Data Transfer Object for Prism usage information
 */
readonly class PrismUsageDto
{
    public function __construct(
        public int $promptTokens = 0,
        public int $completionTokens = 0,
        public int $totalTokens = 0,
        public ?int $thoughtTokens = null,
    ) {
    }

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            promptTokens: $data['promptTokens'] ?? $data['prompt_tokens'] ?? 0,
            completionTokens: $data['completionTokens'] ?? $data['completion_tokens'] ?? 0,
            totalTokens: $data['totalTokens'] ?? $data['total_tokens'] ?? ($data['promptTokens'] ?? 0) + ($data['completionTokens'] ?? 0),
            thoughtTokens: $data['thoughtTokens'] ?? $data['thought_tokens'] ?? null,
        );
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return array_filter([
            'promptTokens' => $this->promptTokens,
            'completionTokens' => $this->completionTokens,
            'totalTokens' => $this->totalTokens,
            'thoughtTokens' => $this->thoughtTokens,
        ], fn ($value) => $value !== null);
    }
}
