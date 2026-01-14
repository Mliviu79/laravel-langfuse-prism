<?php

declare(strict_types=1);

namespace Langfuse\Integration\Prism\Contracts;

use Langfuse\Integration\Prism\DTOs\PrismRequestDto;
use Prism\Prism\Audio\SpeechToTextRequest;
use Prism\Prism\Audio\TextToSpeechRequest;
use Prism\Prism\Contracts\PrismRequest;
use Prism\Prism\Embeddings\Request as EmbeddingsRequest;
use Prism\Prism\Images\Request as ImagesRequest;
use Prism\Prism\Moderation\Request as ModerationRequest;
use Prism\Prism\Structured\Request as StructuredRequest;
use Prism\Prism\Text\Request as TextRequest;

/**
 * Contract for extracting data from Prism requests.
 *
 * Implementations should be able to extract relevant data from any
 * Prism request type and return it as a standardized DTO.
 */
interface PrismRequestExtractorInterface
{
    /**
     * Extract data from a Prism request.
     */
    public function extract(
        PrismRequest|TextRequest|StructuredRequest|EmbeddingsRequest|ImagesRequest|ModerationRequest|TextToSpeechRequest|SpeechToTextRequest $request
    ): PrismRequestDto;
}
