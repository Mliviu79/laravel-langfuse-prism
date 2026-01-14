<?php

declare(strict_types=1);

namespace Langfuse\Integration\Prism\Services;

use Langfuse\Integration\Prism\Contracts\PrismResponseExtractorInterface;
use Langfuse\Integration\Prism\DTOs\PrismResponseDto;
use Langfuse\Integration\Prism\DTOs\PrismUsageDto;
use Prism\Prism\Audio\AudioResponse as TextToSpeechResponse;
use Prism\Prism\Audio\TextResponse as SpeechToTextResponse;
use Prism\Prism\Embeddings\Response as EmbeddingsResponse;
use Prism\Prism\Images\Response as ImagesResponse;
use Prism\Prism\Moderation\Response as ModerationResponse;
use Prism\Prism\Structured\Response as StructuredResponse;
use Prism\Prism\Text\Response as TextResponse;
use Prism\Prism\ValueObjects\Usage;

/**
 * Service for extracting data from Prism responses.
 *
 * Handles all Prism response types and extracts standardized data.
 */
class PrismResponseExtractor implements PrismResponseExtractorInterface
{
    public function extract(
        TextResponse|StructuredResponse|EmbeddingsResponse|ImagesResponse|ModerationResponse|TextToSpeechResponse|SpeechToTextResponse $response
    ): PrismResponseDto {
        return new PrismResponseDto(
            text: $this->extractText($response),
            message: $this->extractMessage($response),
            choices: $this->extractChoices($response),
            usage: $this->extractUsage($response),
            cost: $this->extractCost($response),
            metadata: $this->extractMetadata($response),
            completionStartTime: null, // Not available in Prism responses
            responseTime: null, // Not available in Prism responses
            additionalOutput: $this->extractAdditionalOutput($response),
        );
    }

    private function extractText(mixed $response): ?string
    {
        // TextResponse and StructuredResponse have text property
        if ($response instanceof TextResponse || $response instanceof StructuredResponse) {
            return $response->text;
        }

        // SpeechToTextResponse has text property
        if ($response instanceof SpeechToTextResponse) {
            return $response->text;
        }

        return null;
    }

    private function extractMessage(mixed $response): ?array
    {
        // TextResponse has messages collection
        if ($response instanceof TextResponse) {
            $messages = $response->messages;
            if ($messages->isEmpty()) {
                return null;
            }

            $lastMessage = $messages->last();

            if (method_exists($lastMessage, 'toArray')) {
                return $lastMessage->toArray();
            }

            return (array) $lastMessage;
        }

        return null;
    }

    private function extractChoices(mixed $response): ?array
    {
        // TextResponse and StructuredResponse have steps
        if ($response instanceof TextResponse || $response instanceof StructuredResponse) {
            if ($response->steps->isEmpty()) {
                return null;
            }

            return $response->steps->map(function ($step) {
                return [
                    'text' => $step->text,
                    'finish_reason' => $step->finishReason->value,
                ];
            })->all();
        }

        return null;
    }

    private function extractUsage(mixed $response): ?PrismUsageDto
    {
        // TextResponse and StructuredResponse have usage property
        if ($response instanceof TextResponse || $response instanceof StructuredResponse) {
            return $this->convertUsage($response->usage);
        }

        // EmbeddingsResponse has usage property
        if ($response instanceof EmbeddingsResponse) {
            return $this->convertEmbeddingsUsage($response->usage);
        }

        return null;
    }

    private function convertUsage(Usage $usage): PrismUsageDto
    {
        return new PrismUsageDto(
            promptTokens: $usage->promptTokens,
            completionTokens: $usage->completionTokens,
            totalTokens: $usage->promptTokens + $usage->completionTokens,
            thoughtTokens: $usage->thoughtTokens,
        );
    }

    private function convertEmbeddingsUsage(mixed $usage): PrismUsageDto
    {
        // EmbeddingsUsage only has totalTokens
        $totalTokens = $usage->tokens ?? 0;

        return new PrismUsageDto(
            promptTokens: $totalTokens,
            completionTokens: 0,
            totalTokens: $totalTokens,
            thoughtTokens: null,
        );
    }

    /**
     * @return null
     */
    private function extractCost(mixed $response)
    {
        // Cost information is typically not available in Prism responses directly
        // It would need to be calculated from usage and model pricing
        return null;
    }

    private function extractMetadata(mixed $response): ?array
    {
        $metadata = [];

        // TextResponse has finishReason and meta
        if ($response instanceof TextResponse) {
            $metadata['finish_reason'] = $response->finishReason->value;

            $metadata['request_id'] = $response->meta->id;
            $metadata['model'] = $response->meta->model;
        }

        // StructuredResponse also has these
        if ($response instanceof StructuredResponse) {
            $metadata['finish_reason'] = $response->finishReason->value;

            $metadata['request_id'] = $response->meta->id;
            $metadata['model'] = $response->meta->model;
        }

        // ModerationResponse has flagged info
        if ($response instanceof ModerationResponse) {
            $metadata['flagged'] = $response->isFlagged();
            $metadata['flagged_count'] = count($response->flagged());
        }

        return empty($metadata) ? null : $metadata;
    }

    private function extractAdditionalOutput(mixed $response): ?array
    {
        $additional = [];

        // TextResponse has tool calls and results
        if ($response instanceof TextResponse) {
            if (! empty($response->toolCalls)) {
                $additional['tool_calls'] = array_map(function ($toolCall) {
                    return [
                        'name' => $toolCall->name,
                        'arguments' => $toolCall->arguments(),
                    ];
                }, $response->toolCalls);
            }

            if (! empty($response->toolResults)) {
                $additional['tool_results'] = array_map(function ($toolResult) {
                    return [
                        'name' => $toolResult->toolName,
                        'result' => $toolResult->result,
                    ];
                }, $response->toolResults);
            }

            if (! empty($response->additionalContent)) {
                $additional['additional_content'] = $response->additionalContent;
            }
        }

        // StructuredResponse has structured object
        if ($response instanceof StructuredResponse) {
            $additional['structured_output'] = $response->structured;
        }

        // EmbeddingsResponse
        if ($response instanceof EmbeddingsResponse) {
            $additional['embeddings_count'] = count($response->embeddings);
        }

        // ImagesResponse
        if ($response instanceof ImagesResponse) {
            $additional['images_count'] = count($response->images);
        }

        // ModerationResponse
        if ($response instanceof ModerationResponse) {
            $additional['results_count'] = count($response->results);
        }

        // TextToSpeechResponse
        if ($response instanceof TextToSpeechResponse) {
            $additional['audio_generated'] = true;
        }

        return empty($additional) ? null : $additional;
    }
}
