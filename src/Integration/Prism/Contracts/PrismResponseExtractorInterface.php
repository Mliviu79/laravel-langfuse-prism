<?php

declare(strict_types=1);

namespace Langfuse\Integration\Prism\Contracts;

use Langfuse\Integration\Prism\DTOs\PrismResponseDto;
use Prism\Prism\Audio\AudioResponse as TextToSpeechResponse;
use Prism\Prism\Audio\TextResponse as SpeechToTextResponse;
use Prism\Prism\Embeddings\Response as EmbeddingsResponse;
use Prism\Prism\Images\Response as ImagesResponse;
use Prism\Prism\Moderation\Response as ModerationResponse;
use Prism\Prism\Structured\Response as StructuredResponse;
use Prism\Prism\Text\Response as TextResponse;

/**
 * Contract for extracting data from Prism responses.
 *
 * Implementations should be able to extract relevant data from any
 * Prism response type and return it as a standardized DTO.
 */
interface PrismResponseExtractorInterface
{
    /**
     * Extract data from a Prism response.
     *
     * @param TextResponse|StructuredResponse|EmbeddingsResponse|ImagesResponse|ModerationResponse|TextToSpeechResponse|SpeechToTextResponse $response
     */
    public function extract(
        TextResponse|StructuredResponse|EmbeddingsResponse|ImagesResponse|ModerationResponse|TextToSpeechResponse|SpeechToTextResponse $response
    ): PrismResponseDto;
}
