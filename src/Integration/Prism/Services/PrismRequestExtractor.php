<?php

declare(strict_types=1);

namespace Langfuse\Integration\Prism\Services;

use Langfuse\Integration\Prism\Contracts\PrismRequestExtractorInterface;
use Langfuse\Integration\Prism\DTOs\PrismRequestDto;
use Prism\Prism\Audio\SpeechToTextRequest;
use Prism\Prism\Audio\TextToSpeechRequest;
use Prism\Prism\Contracts\PrismRequest;
use Prism\Prism\Embeddings\Request as EmbeddingsRequest;
use Prism\Prism\Images\Request as ImagesRequest;
use Prism\Prism\Moderation\Request as ModerationRequest;
use Prism\Prism\Structured\Request as StructuredRequest;
use Prism\Prism\Text\Request as TextRequest;
use Prism\Prism\ValueObjects\Messages\SystemMessage;

/**
 * Service for extracting data from Prism requests.
 *
 * Handles all Prism request types and extracts standardized data.
 */
class PrismRequestExtractor implements PrismRequestExtractorInterface
{
    public function extract(
        PrismRequest|TextRequest|StructuredRequest|EmbeddingsRequest|ImagesRequest|ModerationRequest|TextToSpeechRequest|SpeechToTextRequest $request
    ): PrismRequestDto {
        $providerOptions = $request->providerOptions();
        $langfuseOptions = $providerOptions['langfuse'] ?? [];

        return new PrismRequestDto(
            provider: $this->extractProvider($request),
            model: $this->extractModel($request),
            prompt: $this->extractPrompt($request),
            messages: $this->extractMessages($request),
            systemPrompt: $this->extractSystemPrompt($request),
            parameters: $this->extractParameters($request),
            requestId: null, // Not available on Prism requests
            userData: null, // Not available on Prism requests
            additionalInput: $this->extractAdditionalInput($request),
            promptName: $langfuseOptions['prompt_name'] ?? null,
            promptVersion: $langfuseOptions['prompt_version'] ?? null,
        );
    }

    private function extractProvider(mixed $request): ?string
    {
        if (method_exists($request, 'provider')) {
            return $request->provider();
        }

        return null;
    }

    private function extractModel(mixed $request): ?string
    {
        if (method_exists($request, 'model')) {
            return $request->model();
        }

        return null;
    }

    private function extractPrompt(mixed $request): ?string
    {
        // TextRequest and StructuredRequest have prompt()
        if ($request instanceof TextRequest || $request instanceof StructuredRequest) {
            return $request->prompt();
        }

        // TextToSpeechRequest - extract text input
        if ($request instanceof TextToSpeechRequest && method_exists($request, 'text')) {
            return $request->text();
        }

        return null;
    }

    private function extractMessages(mixed $request): ?array
    {
        // TextRequest and StructuredRequest have messages()
        if ($request instanceof TextRequest || $request instanceof StructuredRequest) {
            $messages = $request->messages();
            if (empty($messages)) {
                return null;
            }

            return array_map(function ($message) {
                if (method_exists($message, 'toArray')) {
                    return $message->toArray();
                }

                return (array) $message;
            }, $messages);
        }

        return null;
    }

    private function extractSystemPrompt(mixed $request): ?string
    {
        // TextRequest and StructuredRequest have systemPrompts()
        if ($request instanceof TextRequest || $request instanceof StructuredRequest) {
            $systemPrompts = $request->systemPrompts();
            if (empty($systemPrompts)) {
                return null;
            }

            return $this->combineSystemPrompts($systemPrompts);
        }

        return null;
    }

    /**
     * Combine multiple system prompts into a single string.
     *
     * @param  SystemMessage[]  $systemPrompts
     */
    private function combineSystemPrompts(array $systemPrompts): string
    {
        $combined = [];

        foreach ($systemPrompts as $prompt) {
            // SystemMessage has a public readonly property, not a method
            $combined[] = $prompt->content;
        }

        return implode("\n\n", $combined);
    }

    private function extractParameters(mixed $request): ?array
    {
        $params = [];

        // TextRequest and StructuredRequest have these methods
        if ($request instanceof TextRequest || $request instanceof StructuredRequest) {
            if ($request->maxTokens() !== null) {
                $params['max_tokens'] = $request->maxTokens();
            }

            if ($request->temperature() !== null) {
                $params['temperature'] = $request->temperature();
            }

            if ($request->topP() !== null) {
                $params['top_p'] = $request->topP();
            }
        }

        return empty($params) ? null : $params;
    }

    private function extractAdditionalInput(mixed $request): ?array
    {
        $additional = [];

        // EmbeddingsRequest
        if ($request instanceof EmbeddingsRequest) {
            $additional['inputs'] = $request->inputs();
            if ($request->hasImages()) {
                $additional['has_images'] = true;
                $additional['image_count'] = count($request->images());
            }
        }

        // ModerationRequest
        if ($request instanceof ModerationRequest) {
            $additional['inputs'] = $request->inputs();
        }

        // ImagesRequest
        if ($request instanceof ImagesRequest) {
            $additional['prompt'] = $request->prompt();
        }

        // SpeechToTextRequest
        if ($request instanceof SpeechToTextRequest) {
            // Audio file info if available
            if (method_exists($request, 'audio')) {
                $additional['has_audio'] = true;
            }
        }

        return empty($additional) ? null : $additional;
    }
}
