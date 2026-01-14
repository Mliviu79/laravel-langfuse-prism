<?php

declare(strict_types=1);

namespace Langfuse\Media\Processors;

use Langfuse\Media\Contracts\MediaProcessorInterface;
use Langfuse\Support\Enums\MediaContentType;

/**
 * Video media processor
 */
class VideoProcessor implements MediaProcessorInterface
{
    public function canProcess(MediaContentType $contentType): bool
    {
        return $contentType->isVideo();
    }

    public function process(mixed $content, MediaContentType $contentType): array
    {
        if (! is_string($content)) {
            throw new \InvalidArgumentException('Video content must be a string');
        }

        $metadata = [
            'contentType' => $contentType->value,
            'size' => strlen($content),
        ];

        try {
            $videoInfo = $this->getVideoInfo($content, $contentType);
            $metadata = array_merge($metadata, $videoInfo);
        } catch (\Throwable $e) {
            $metadata['processing_error'] = $e->getMessage();
        }

        return $metadata;
    }

    public function getSupportedContentTypes(): array
    {
        return MediaContentType::getVideoTypes();
    }

    public function getName(): string
    {
        return 'video-processor';
    }

    /**
     * Extract video information
     */
    private function getVideoInfo(string $content, MediaContentType $contentType): array
    {
        $info = [];

        // Basic format detection based on content type and magic bytes
        $info['format'] = match ($contentType) {
            MediaContentType::VIDEO_MP4 => 'mp4',
            MediaContentType::VIDEO_WEBM => 'webm',
            MediaContentType::VIDEO_OGG => 'ogg',
            default => 'unknown',
        };

        // Check magic bytes to verify format
        $magicBytes = substr($content, 0, 12);
        $info['magicBytesValid'] = $this->validateMagicBytes($magicBytes, $contentType);

        // Try to extract basic container information
        try {
            $containerInfo = $this->extractContainerInfo($content, $contentType);
            $info = array_merge($info, $containerInfo);
        } catch (\Throwable) {
            // Ignore container parsing errors
        }

        return $info;
    }

    /**
     * Validate magic bytes for video format
     */
    private function validateMagicBytes(string $magicBytes, MediaContentType $contentType): bool
    {
        return match ($contentType) {
            MediaContentType::VIDEO_MP4 => str_contains($magicBytes, 'ftyp'),
            MediaContentType::VIDEO_WEBM => str_starts_with($magicBytes, "\x1A\x45\xDF\xA3"),
            MediaContentType::VIDEO_OGG => str_starts_with($magicBytes, 'OggS'),
            default => false,
        };
    }

    /**
     * Extract basic container information
     */
    private function extractContainerInfo(string $content, MediaContentType $contentType): array
    {
        return match ($contentType) {
            MediaContentType::VIDEO_MP4 => $this->extractMp4Info($content),
            MediaContentType::VIDEO_WEBM => $this->extractWebmInfo($content),
            MediaContentType::VIDEO_OGG => $this->extractOggInfo($content),
            default => [],
        };
    }

    /**
     * Extract MP4 container information
     */
    private function extractMp4Info(string $content): array
    {
        $info = [];

        // Look for common MP4 atoms/boxes
        $atoms = $this->findMp4Atoms($content);
        $info['atoms'] = array_keys($atoms);

        // Extract brand information from ftyp atom
        if (isset($atoms['ftyp'])) {
            $ftypData = substr($content, $atoms['ftyp']['offset'] + 8, $atoms['ftyp']['size'] - 8);
            $majorBrand = substr($ftypData, 0, 4);
            $info['majorBrand'] = $majorBrand;

            // Common brand mappings
            $info['brandDescription'] = match ($majorBrand) {
                'isom' => 'ISO Base Media',
                'mp41' => 'MP4 v1',
                'mp42' => 'MP4 v2',
                'avc1' => 'H.264/AVC',
                'dash' => 'DASH',
                default => 'Unknown',
            };
        }

        return $info;
    }

    /**
     * Extract WebM container information
     */
    private function extractWebmInfo(string $content): array
    {
        $info = [];

        // WebM is based on Matroska, look for EBML header
        if (str_starts_with($content, "\x1A\x45\xDF\xA3")) {
            $info['container'] = 'matroska/webm';

            // Try to find DocType
            $docTypePos = strpos($content, "\x42\x82");
            if ($docTypePos !== false) {
                $docType = substr($content, $docTypePos + 3, 4);
                $info['docType'] = trim($docType);
            }
        }

        return $info;
    }

    /**
     * Extract OGG container information
     */
    private function extractOggInfo(string $content): array
    {
        $info = [];

        if (str_starts_with($content, 'OggS')) {
            $info['container'] = 'ogg';

            // Extract version from OGG header
            $version = ord($content[4]);
            $info['version'] = $version;

            // Extract page flags
            $flags = ord($content[5]);
            $info['flags'] = [
                'continued' => ($flags & 0x01) !== 0,
                'first' => ($flags & 0x02) !== 0,
                'last' => ($flags & 0x04) !== 0,
            ];
        }

        return $info;
    }

    /**
     * Find MP4 atoms in the content
     */
    private function findMp4Atoms(string $content): array
    {
        $atoms = [];
        $offset = 0;
        $contentLength = strlen($content);

        while ($offset < $contentLength - 8) {
            // Read atom size (4 bytes, big-endian)
            $sizeBytes = substr($content, $offset, 4);
            $size = unpack('N', $sizeBytes)[1];

            // Read atom type (4 bytes)
            $type = substr($content, $offset + 4, 4);

            if ($size < 8 || $offset + $size > $contentLength) {
                break;
            }

            $atoms[$type] = [
                'offset' => $offset,
                'size' => $size,
            ];

            $offset += $size;

            // Prevent infinite loops
            if (count($atoms) > 100) {
                break;
            }
        }

        return $atoms;
    }
}
