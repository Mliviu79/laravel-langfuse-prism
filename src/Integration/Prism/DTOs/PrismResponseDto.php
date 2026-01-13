<?php

declare(strict_types=1);

namespace Langfuse\Integration\Prism\DTOs;

use DateTime;

/**
 * Data Transfer Object for Prism response data
 */
readonly class PrismResponseDto
{
    public function __construct(
        public ?string $text = null,
        public ?array $message = null,
        public ?array $choices = null,
        public ?array $additionalOutput = null,
        public ?PrismUsageDto $usage = null,
        public ?PrismCostDto $cost = null,
        public ?array $metadata = null,
        public ?DateTime $completionStartTime = null,
        public ?float $responseTime = null,
    ) {
    }

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            text: $data['text'] ?? null,
            message: $data['message'] ?? null,
            choices: $data['choices'] ?? null,
            additionalOutput: $data['additionalOutput'] ?? null,
            usage: isset($data['usage']) ? PrismUsageDto::fromArray($data['usage']) : null,
            cost: isset($data['cost']) ? PrismCostDto::fromArray($data['cost']) : null,
            metadata: $data['metadata'] ?? null,
            completionStartTime: isset($data['completionStartTime']) && $data['completionStartTime'] instanceof DateTime
                ? $data['completionStartTime']
                : null,
            responseTime: $data['responseTime'] ?? null,
        );
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return array_filter([
            'text' => $this->text,
            'message' => $this->message,
            'choices' => $this->choices,
            'additionalOutput' => $this->additionalOutput,
            'usage' => $this->usage?->toArray(),
            'cost' => $this->cost?->toArray(),
            'metadata' => $this->metadata,
            'completionStartTime' => $this->completionStartTime?->format('c'),
            'responseTime' => $this->responseTime,
        ], fn ($value) => $value !== null);
    }
}
