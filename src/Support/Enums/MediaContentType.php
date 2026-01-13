<?php

declare(strict_types=1);

namespace Langfuse\Support\Enums;

enum MediaContentType: string
{
    case IMAGE_PNG = 'image/png';
    case IMAGE_JPEG = 'image/jpeg';
    case IMAGE_GIF = 'image/gif';
    case IMAGE_WEBP = 'image/webp';
    case IMAGE_SVG = 'image/svg+xml';

    case VIDEO_MP4 = 'video/mp4';
    case VIDEO_WEBM = 'video/webm';
    case VIDEO_OGG = 'video/ogg';

    case AUDIO_MP3 = 'audio/mpeg';
    case AUDIO_WAV = 'audio/wav';
    case AUDIO_OGG = 'audio/ogg';
    case AUDIO_WEBM = 'audio/webm';

    case DOCUMENT_PDF = 'application/pdf';
    case DOCUMENT_TXT = 'text/plain';
    case DOCUMENT_JSON = 'application/json';
    case DOCUMENT_XML = 'application/xml';
    case DOCUMENT_CSV = 'text/csv';

    /**
     * Check if this is an image content type
     */
    public function isImage(): bool
    {
        return str_starts_with($this->value, 'image/');
    }

    /**
     * Check if this is a video content type
     */
    public function isVideo(): bool
    {
        return str_starts_with($this->value, 'video/');
    }

    /**
     * Check if this is an audio content type
     */
    public function isAudio(): bool
    {
        return str_starts_with($this->value, 'audio/');
    }

    /**
     * Check if this is a document content type
     */
    public function isDocument(): bool
    {
        return in_array($this->value, [
            'application/pdf',
            'text/plain',
            'application/json',
            'application/xml',
            'text/csv',
        ], true);
    }

    /**
     * Get the file extension for this content type
     */
    public function getFileExtension(): string
    {
        return match ($this) {
            self::IMAGE_PNG => 'png',
            self::IMAGE_JPEG => 'jpg',
            self::IMAGE_GIF => 'gif',
            self::IMAGE_WEBP => 'webp',
            self::IMAGE_SVG => 'svg',
            self::VIDEO_MP4 => 'mp4',
            self::VIDEO_WEBM => 'webm',
            self::VIDEO_OGG => 'ogv',
            self::AUDIO_MP3 => 'mp3',
            self::AUDIO_WAV => 'wav',
            self::AUDIO_OGG => 'ogg',
            self::AUDIO_WEBM => 'webm',
            self::DOCUMENT_PDF => 'pdf',
            self::DOCUMENT_TXT => 'txt',
            self::DOCUMENT_JSON => 'json',
            self::DOCUMENT_XML => 'xml',
            self::DOCUMENT_CSV => 'csv',
        };
    }

    /**
     * Create from file extension
     */
    public static function fromFileExtension(string $extension): ?self
    {
        $extension = strtolower(ltrim($extension, '.'));

        return match ($extension) {
            'png' => self::IMAGE_PNG,
            'jpg', 'jpeg' => self::IMAGE_JPEG,
            'gif' => self::IMAGE_GIF,
            'webp' => self::IMAGE_WEBP,
            'svg' => self::IMAGE_SVG,
            'mp4' => self::VIDEO_MP4,
            'webm' => self::VIDEO_WEBM,
            'ogv' => self::VIDEO_OGG,
            'mp3' => self::AUDIO_MP3,
            'wav' => self::AUDIO_WAV,
            'ogg' => self::AUDIO_OGG,
            'pdf' => self::DOCUMENT_PDF,
            'txt' => self::DOCUMENT_TXT,
            'json' => self::DOCUMENT_JSON,
            'xml' => self::DOCUMENT_XML,
            'csv' => self::DOCUMENT_CSV,
            default => null,
        };
    }

    /**
     * Get all supported image types
     */
    public static function getImageTypes(): array
    {
        return [
            self::IMAGE_PNG,
            self::IMAGE_JPEG,
            self::IMAGE_GIF,
            self::IMAGE_WEBP,
            self::IMAGE_SVG,
        ];
    }

    /**
     * Get all supported video types
     */
    public static function getVideoTypes(): array
    {
        return [
            self::VIDEO_MP4,
            self::VIDEO_WEBM,
            self::VIDEO_OGG,
        ];
    }

    /**
     * Get all supported audio types
     */
    public static function getAudioTypes(): array
    {
        return [
            self::AUDIO_MP3,
            self::AUDIO_WAV,
            self::AUDIO_OGG,
            self::AUDIO_WEBM,
        ];
    }

    /**
     * Get all supported document types
     */
    public static function getDocumentTypes(): array
    {
        return [
            self::DOCUMENT_PDF,
            self::DOCUMENT_TXT,
            self::DOCUMENT_JSON,
            self::DOCUMENT_XML,
            self::DOCUMENT_CSV,
        ];
    }
}