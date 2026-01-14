<?php

declare(strict_types=1);

namespace Langfuse\Media\Processors;

use Langfuse\Media\Contracts\MediaProcessorInterface;
use Langfuse\Support\Enums\MediaContentType;

/**
 * Image media processor
 */
class ImageProcessor implements MediaProcessorInterface
{
    public function canProcess(MediaContentType $contentType): bool
    {
        return $contentType->isImage();
    }

    public function process(mixed $content, MediaContentType $contentType): array
    {
        if (! is_string($content)) {
            throw new \InvalidArgumentException('Image content must be a string');
        }

        $metadata = [
            'contentType' => $contentType->value,
            'size' => strlen($content),
        ];

        // Try to extract image dimensions and other info
        try {
            $imageInfo = $this->getImageInfo($content);
            $metadata = array_merge($metadata, $imageInfo);
        } catch (\Throwable $e) {
            $metadata['processing_error'] = $e->getMessage();
        }

        return $metadata;
    }

    public function getSupportedContentTypes(): array
    {
        return MediaContentType::getImageTypes();
    }

    public function getName(): string
    {
        return 'image-processor';
    }

    /**
     * Extract image information
     */
    private function getImageInfo(string $content): array
    {
        // Create temporary file for processing
        $tempFile = tempnam(sys_get_temp_dir(), 'langfuse_image_');
        file_put_contents($tempFile, $content);

        try {
            $imageInfo = getimagesize($tempFile);

            if ($imageInfo === false) {
                throw new \RuntimeException('Unable to get image size');
            }

            $info = [
                'width' => $imageInfo[0],
                'height' => $imageInfo[1],
                'type' => $imageInfo[2],
                'mime' => $imageInfo['mime'],
                'channels' => $imageInfo['channels'] ?? null,
                'bits' => $imageInfo['bits'] ?? null,
            ];

            // Try to get EXIF data for JPEG images
            if ($imageInfo[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
                try {
                    $exif = exif_read_data($tempFile);
                    if ($exif !== false) {
                        $info['exif'] = $this->sanitizeExifData($exif);
                    }
                } catch (\Throwable) {
                    // Ignore EXIF errors
                }
            }

            return $info;
        } finally {
            unlink($tempFile);
        }
    }

    /**
     * Sanitize EXIF data to remove sensitive information
     */
    private function sanitizeExifData(array $exif): array
    {
        $allowedKeys = [
            'DateTime',
            'Make',
            'Model',
            'Software',
            'ImageWidth',
            'ImageLength',
            'XResolution',
            'YResolution',
            'ResolutionUnit',
            'ColorSpace',
            'ExposureTime',
            'FNumber',
            'ISO',
            'FocalLength',
        ];

        return array_intersect_key($exif, array_flip($allowedKeys));
    }
}
