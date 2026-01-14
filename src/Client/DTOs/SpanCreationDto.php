<?php

declare(strict_types=1);

namespace Langfuse\Client\DTOs;

use Langfuse\Support\Enums\ObservationType;
use Langfuse\Support\Enums\SpanLevel;

/**
 * Data Transfer Object for span creation
 */
readonly class SpanCreationDto
{
    public function __construct(
        public string $name,
        public ObservationType $type = ObservationType::SPAN,
        public mixed $input = null,
        public mixed $output = null,
        public ?array $metadata = null,
        public ?string $version = null,
        public ?SpanLevel $level = null,
        public ?string $statusMessage = null,
        public ?string $parentId = null,
        public ?string $model = null,
    ) {}

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            type: $data['type'] ?? ObservationType::SPAN,
            input: $data['input'] ?? null,
            output: $data['output'] ?? null,
            metadata: $data['metadata'] ?? null,
            version: $data['version'] ?? null,
            level: $data['level'] ?? null,
            statusMessage: $data['statusMessage'] ?? null,
            parentId: $data['parentId'] ?? null,
            model: $data['model'] ?? null,
        );
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'type' => $this->type,
            'input' => $this->input,
            'output' => $this->output,
            'metadata' => $this->metadata,
            'version' => $this->version,
            'level' => $this->level,
            'statusMessage' => $this->statusMessage,
            'parentId' => $this->parentId,
            'model' => $this->model,
        ], fn ($value) => $value !== null);
    }
}
