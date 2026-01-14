<?php

declare(strict_types=1);

namespace Langfuse\Media\Contracts;

use Langfuse\Support\Enums\MediaContentType;

interface MediaProcessorInterface
{
    /**
     * Check if this processor can handle the given content type
     */
    public function canProcess(MediaContentType $contentType): bool;

    /**
     * Process media content and return metadata
     *
     * @param  mixed  $content  The media content (string, resource, etc.)
     * @param  MediaContentType  $contentType  The content type
     * @return array Processed media metadata including URL, dimensions, etc.
     */
    public function process(mixed $content, MediaContentType $contentType): array;

    /**
     * Get the supported content types for this processor
     *
     * @return MediaContentType[]
     */
    public function getSupportedContentTypes(): array;

    /**
     * Get the processor name/identifier
     */
    public function getName(): string;
}
