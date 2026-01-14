<?php

declare(strict_types=1);

namespace Langfuse\Client\DTOs;

/**
 * Data Transfer Object for trace updates
 */
readonly class TraceUpdateDto
{
    public function __construct(
        public ?string $name = null,
        public ?string $userId = null,
        public ?string $sessionId = null,
        public ?string $version = null,
        public mixed $input = null,
        public mixed $output = null,
        public ?array $metadata = null,
        public ?array $tags = null,
        public ?bool $public = null,
    ) {}

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? null,
            userId: $data['userId'] ?? null,
            sessionId: $data['sessionId'] ?? null,
            version: $data['version'] ?? null,
            input: $data['input'] ?? null,
            output: $data['output'] ?? null,
            metadata: $data['metadata'] ?? null,
            tags: $data['tags'] ?? null,
            public: $data['public'] ?? null,
        );
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'userId' => $this->userId,
            'sessionId' => $this->sessionId,
            'version' => $this->version,
            'input' => $this->input,
            'output' => $this->output,
            'metadata' => $this->metadata,
            'tags' => $this->tags,
            'public' => $this->public,
        ], fn ($value) => $value !== null);
    }
}
