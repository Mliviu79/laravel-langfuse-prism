<?php

declare(strict_types=1);

namespace Langfuse\Integration\Prism\DTOs;

/**
 * Data Transfer Object for Prism request data
 */
readonly class PrismRequestDto
{
    public function __construct(
        public ?string $provider = null,
        public ?string $model = null,
        public ?string $prompt = null,
        public ?array $messages = null,
        public ?string $systemPrompt = null,
        public ?array $parameters = null,
        public ?array $additionalInput = null,
        public ?string $requestId = null,
        public ?array $userData = null,
        public ?string $promptName = null,
        public ?int $promptVersion = null,
    ) {}

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            provider: $data['provider'] ?? null,
            model: $data['model'] ?? null,
            prompt: $data['prompt'] ?? null,
            messages: $data['messages'] ?? null,
            systemPrompt: $data['systemPrompt'] ?? null,
            parameters: $data['parameters'] ?? null,
            additionalInput: $data['additionalInput'] ?? null,
            requestId: $data['requestId'] ?? null,
            userData: $data['userData'] ?? null,
            promptName: $data['promptName'] ?? null,
            promptVersion: $data['promptVersion'] ?? null,
        );
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return array_filter([
            'provider' => $this->provider,
            'model' => $this->model,
            'prompt' => $this->prompt,
            'messages' => $this->messages,
            'systemPrompt' => $this->systemPrompt,
            'parameters' => $this->parameters,
            'additionalInput' => $this->additionalInput,
            'requestId' => $this->requestId,
            'userData' => $this->userData,
            'promptName' => $this->promptName,
            'promptVersion' => $this->promptVersion,
        ], fn ($value) => $value !== null);
    }
}
