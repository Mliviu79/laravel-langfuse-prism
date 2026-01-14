<?php

declare(strict_types=1);

namespace Langfuse\Observability\DTOs;

use DateTime;
use Langfuse\Support\Enums\ObservationType;
use Langfuse\Support\Enums\SpanLevel;

/**
 * Data Transfer Object for observation creation
 */
readonly class ObservationDto
{
    public function __construct(
        public string $name,
        public ObservationType $type,
        public mixed $input = null,
        public mixed $output = null,
        public ?array $metadata = null,
        public ?string $version = null,
        public ?SpanLevel $level = null,
        public ?string $statusMessage = null,
        public ?DateTime $completionStartTime = null,
        public ?string $model = null,
        public ?array $modelParameters = null,
        public ?array $usageDetails = null,
        public ?array $costDetails = null,
    ) {}

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            type: $data['type'],
            input: $data['input'] ?? null,
            output: $data['output'] ?? null,
            metadata: $data['metadata'] ?? null,
            version: $data['version'] ?? null,
            level: $data['level'] ?? null,
            statusMessage: $data['statusMessage'] ?? null,
            completionStartTime: isset($data['completionStartTime']) && $data['completionStartTime'] instanceof DateTime
                ? $data['completionStartTime']
                : null,
            model: $data['model'] ?? null,
            modelParameters: $data['modelParameters'] ?? null,
            usageDetails: $data['usageDetails'] ?? null,
            costDetails: $data['costDetails'] ?? null,
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
            'completionStartTime' => $this->completionStartTime?->format('c'),
            'model' => $this->model,
            'modelParameters' => $this->modelParameters,
            'usageDetails' => $this->usageDetails,
            'costDetails' => $this->costDetails,
        ], fn ($value) => $value !== null);
    }
}
