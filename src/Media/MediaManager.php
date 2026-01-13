<?php

declare(strict_types=1);

namespace Langfuse\Media;

use Langfuse\Api\Contracts\ApiClientInterface;
use Langfuse\Media\Contracts\MediaProcessorInterface;
use Langfuse\Support\Enums\MediaContentType;
use Langfuse\Support\Exceptions\LangfuseException;

/**
 * Media manager for processing and uploading media content to Langfuse
 */
class MediaManager
{
    /** @var MediaProcessorInterface[] */
    private array $processors = [];

    public function __construct(
        private readonly ApiClientInterface $apiClient
    ) {
        $this->registerDefaultProcessors();
    }

    /**
     * Register a media processor
     */
    public function registerProcessor(MediaProcessorInterface $processor): void
    {
        $this->processors[] = $processor;
    }

    /**
     * Process media content in data structure
     */
    public function findAndProcessMedia(
        mixed $data,
        string $field,
        string $traceId,
        string $observationId
    ): mixed {
        if (!is_array($data)) {
            return $this->processValue($data, $field, $traceId, $observationId);
        }

        $processedData = [];
        foreach ($data as $key => $value) {
            $processedData[$key] = $this->findAndProcessMedia(
                $value,
                "{$field}.{$key}",
                $traceId,
                $observationId
            );
        }

        return $processedData;
    }

    /**
     * Process a single value that might contain media
     */
    private function processValue(mixed $value, string $field, string $traceId, string $observationId): mixed
    {
        // Check if value looks like media content
        if (!$this->isMediaContent($value)) {
            return $value;
        }

        try {
            return $this->processMedia($value, $field, $traceId, $observationId);
        } catch (\Throwable $e) {
            // Log error but don't fail the entire operation
            if (function_exists('report')) {
                report($e);
            }

            return $value; // Return original value if processing fails
        }
    }

    /**
     * Check if a value might contain media content
     */
    private function isMediaContent(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        // Check for data URLs
        if (str_starts_with($value, 'data:')) {
            return true;
        }

        // Check for base64 encoded data (simple heuristic)
        if (strlen($value) > 100 && preg_match('/^[A-Za-z0-9+\/]+={0,2}$/', $value)) {
            return true;
        }

        // Check for file paths to supported media types
        $extension = pathinfo($value, PATHINFO_EXTENSION);
        return MediaContentType::fromFileExtension($extension) !== null;
    }

    /**
     * Process media content
     */
    private function processMedia(string $content, string $field, string $traceId, string $observationId): array
    {
        $mediaInfo = $this->extractMediaInfo($content);
        $contentType = $mediaInfo['contentType'];
        $binaryData = $mediaInfo['data'];

        // Find appropriate processor
        $processor = $this->findProcessor($contentType);
        if (!$processor) {
            throw new LangfuseException("No processor found for content type: {$contentType->value}");
        }

        // Process the media
        $processedData = $processor->process($binaryData, $contentType);

        // Upload to Langfuse
        $uploadResult = $this->uploadMedia($contentType, $binaryData);

        return [
            'type' => 'langfuse_media',
            'contentType' => $contentType->value,
            'url' => $uploadResult['url'] ?? null,
            'mediaId' => $uploadResult['mediaId'] ?? null,
            'field' => $field,
            'traceId' => $traceId,
            'observationId' => $observationId,
            'metadata' => $processedData,
        ];
    }

    /**
     * Extract media information from content
     */
    private function extractMediaInfo(string $content): array
    {
        // Handle data URLs (data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAA...)
        if (str_starts_with($content, 'data:')) {
            preg_match('/^data:([^;]+);base64,(.+)$/', $content, $matches);

            if (count($matches) === 3) {
                $mimeType = $matches[1];
                $base64Data = $matches[2];

                $contentType = $this->mimeTypeToContentType($mimeType);
                $binaryData = base64_decode($base64Data);

                return [
                    'contentType' => $contentType,
                    'data' => $binaryData,
                ];
            }
        }

        // Handle file paths
        if (is_file($content)) {
            $extension = pathinfo($content, PATHINFO_EXTENSION);
            $contentType = MediaContentType::fromFileExtension($extension);

            if ($contentType) {
                return [
                    'contentType' => $contentType,
                    'data' => file_get_contents($content),
                ];
            }
        }

        // Handle base64 encoded data (assume PNG if no other info available)
        if (preg_match('/^[A-Za-z0-9+\/]+={0,2}$/', $content)) {
            return [
                'contentType' => MediaContentType::IMAGE_PNG,
                'data' => base64_decode($content),
            ];
        }

        throw new LangfuseException("Unable to extract media info from content");
    }

    /**
     * Convert MIME type to MediaContentType enum
     */
    private function mimeTypeToContentType(string $mimeType): MediaContentType
    {
        return match ($mimeType) {
            'image/png' => MediaContentType::IMAGE_PNG,
            'image/jpeg', 'image/jpg' => MediaContentType::IMAGE_JPEG,
            'image/gif' => MediaContentType::IMAGE_GIF,
            'image/webp' => MediaContentType::IMAGE_WEBP,
            'image/svg+xml' => MediaContentType::IMAGE_SVG,
            'video/mp4' => MediaContentType::VIDEO_MP4,
            'video/webm' => MediaContentType::VIDEO_WEBM,
            'video/ogg' => MediaContentType::VIDEO_OGG,
            'audio/mpeg' => MediaContentType::AUDIO_MP3,
            'audio/wav' => MediaContentType::AUDIO_WAV,
            'audio/ogg' => MediaContentType::AUDIO_OGG,
            'audio/webm' => MediaContentType::AUDIO_WEBM,
            'application/pdf' => MediaContentType::DOCUMENT_PDF,
            'text/plain' => MediaContentType::DOCUMENT_TXT,
            'application/json' => MediaContentType::DOCUMENT_JSON,
            'application/xml' => MediaContentType::DOCUMENT_XML,
            'text/csv' => MediaContentType::DOCUMENT_CSV,
            default => throw new LangfuseException("Unsupported MIME type: {$mimeType}"),
        };
    }

    /**
     * Find appropriate processor for content type
     */
    private function findProcessor(MediaContentType $contentType): ?MediaProcessorInterface
    {
        foreach ($this->processors as $processor) {
            if ($processor->canProcess($contentType)) {
                return $processor;
            }
        }

        return null;
    }

    /**
     * Upload media to Langfuse
     */
    private function uploadMedia(MediaContentType $contentType, string $binaryData): array
    {
        return $this->apiClient->uploadMedia($contentType->value, $binaryData);
    }

    /**
     * Register default media processors
     */
    private function registerDefaultProcessors(): void
    {
        $this->registerProcessor(new Processors\ImageProcessor());
        $this->registerProcessor(new Processors\VideoProcessor());
        $this->registerProcessor(new Processors\AudioProcessor());
        $this->registerProcessor(new Processors\DocumentProcessor());
    }
}