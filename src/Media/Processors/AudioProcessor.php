<?php

declare(strict_types=1);

namespace Langfuse\Media\Processors;

use Langfuse\Media\Contracts\MediaProcessorInterface;
use Langfuse\Support\Enums\MediaContentType;

/**
 * Audio media processor
 */
class AudioProcessor implements MediaProcessorInterface
{
    public function canProcess(MediaContentType $contentType): bool
    {
        return $contentType->isAudio();
    }

    public function process(mixed $content, MediaContentType $contentType): array
    {
        if (!is_string($content)) {
            throw new \InvalidArgumentException('Audio content must be a string');
        }

        $metadata = [
            'contentType' => $contentType->value,
            'size' => strlen($content),
        ];

        try {
            $audioInfo = $this->getAudioInfo($content, $contentType);
            $metadata = array_merge($metadata, $audioInfo);
        } catch (\Throwable $e) {
            $metadata['processing_error'] = $e->getMessage();
        }

        return $metadata;
    }

    public function getSupportedContentTypes(): array
    {
        return MediaContentType::getAudioTypes();
    }

    public function getName(): string
    {
        return 'audio-processor';
    }

    /**
     * Extract audio information
     */
    private function getAudioInfo(string $content, MediaContentType $contentType): array
    {
        $info = [];

        // Basic format detection
        $info['format'] = match ($contentType) {
            MediaContentType::AUDIO_MP3 => 'mp3',
            MediaContentType::AUDIO_WAV => 'wav',
            MediaContentType::AUDIO_OGG => 'ogg',
            MediaContentType::AUDIO_WEBM => 'webm',
            default => 'unknown',
        };

        // Validate and extract format-specific information
        try {
            $formatInfo = match ($contentType) {
                MediaContentType::AUDIO_MP3 => $this->extractMp3Info($content),
                MediaContentType::AUDIO_WAV => $this->extractWavInfo($content),
                MediaContentType::AUDIO_OGG => $this->extractOggInfo($content),
                MediaContentType::AUDIO_WEBM => $this->extractWebmInfo($content),
                default => [],
            };

            $info = array_merge($info, $formatInfo);
        } catch (\Throwable) {
            // Ignore format parsing errors
        }

        return $info;
    }

    /**
     * Extract MP3 information
     */
    private function extractMp3Info(string $content): array
    {
        $info = [];

        // Check for MP3 frame header
        $frameStart = $this->findMp3FrameStart($content);
        if ($frameStart !== false) {
            $info['validMp3'] = true;

            // Extract frame header information
            $header = substr($content, $frameStart, 4);
            $frameInfo = $this->parseMp3FrameHeader($header);
            $info = array_merge($info, $frameInfo);
        } else {
            $info['validMp3'] = false;
        }

        // Check for ID3 tags
        $id3Info = $this->extractId3Tags($content);
        if (!empty($id3Info)) {
            $info['id3'] = $id3Info;
        }

        return $info;
    }

    /**
     * Extract WAV information
     */
    private function extractWavInfo(string $content): array
    {
        $info = [];

        // Check RIFF header
        if (!str_starts_with($content, 'RIFF')) {
            return ['validWav' => false];
        }

        $info['validWav'] = true;

        // Check WAV signature
        $wavSignature = substr($content, 8, 4);
        if ($wavSignature !== 'WAVE') {
            return array_merge($info, ['validWav' => false]);
        }

        // Find and parse fmt chunk
        $fmtChunkPos = strpos($content, 'fmt ');
        if ($fmtChunkPos !== false) {
            $fmtInfo = $this->parseWavFmtChunk($content, $fmtChunkPos);
            $info = array_merge($info, $fmtInfo);
        }

        return $info;
    }

    /**
     * Extract OGG audio information
     */
    private function extractOggInfo(string $content): array
    {
        $info = [];

        if (str_starts_with($content, 'OggS')) {
            $info['validOgg'] = true;
            $info['container'] = 'ogg';

            // Look for Vorbis header
            if (str_contains($content, 'vorbis')) {
                $info['codec'] = 'vorbis';
            } elseif (str_contains($content, 'FLAC')) {
                $info['codec'] = 'flac';
            } elseif (str_contains($content, 'OpusHead')) {
                $info['codec'] = 'opus';
            }
        } else {
            $info['validOgg'] = false;
        }

        return $info;
    }

    /**
     * Extract WebM audio information
     */
    private function extractWebmInfo(string $content): array
    {
        $info = [];

        // WebM audio uses Matroska container
        if (str_starts_with($content, "\x1A\x45\xDF\xA3")) {
            $info['validWebm'] = true;
            $info['container'] = 'matroska/webm';

            // Common WebM audio codecs
            if (str_contains($content, 'A_VORBIS')) {
                $info['codec'] = 'vorbis';
            } elseif (str_contains($content, 'A_OPUS')) {
                $info['codec'] = 'opus';
            }
        } else {
            $info['validWebm'] = false;
        }

        return $info;
    }

    /**
     * Find MP3 frame start
     */
    private function findMp3FrameStart(string $content): int|false
    {
        $length = strlen($content);

        for ($i = 0; $i < $length - 4; $i++) {
            // Look for frame sync (11 bits set)
            if (ord($content[$i]) === 0xFF && (ord($content[$i + 1]) & 0xE0) === 0xE0) {
                return $i;
            }
        }

        return false;
    }

    /**
     * Parse MP3 frame header
     */
    private function parseMp3FrameHeader(string $header): array
    {
        $bytes = array_map('ord', str_split($header));

        // MPEG version
        $version = ($bytes[1] >> 3) & 0x03;
        $versionMap = [0 => 'MPEG 2.5', 2 => 'MPEG 2', 3 => 'MPEG 1'];

        // Layer
        $layer = ($bytes[1] >> 1) & 0x03;
        $layerMap = [1 => 'Layer III', 2 => 'Layer II', 3 => 'Layer I'];

        // Bitrate
        $bitrateIndex = ($bytes[2] >> 4) & 0x0F;

        // Sample rate
        $sampleRateIndex = ($bytes[2] >> 2) & 0x03;

        return [
            'mpegVersion' => $versionMap[$version] ?? 'Unknown',
            'layer' => $layerMap[$layer] ?? 'Unknown',
            'bitrateIndex' => $bitrateIndex,
            'sampleRateIndex' => $sampleRateIndex,
            'protected' => ($bytes[1] & 0x01) === 0,
        ];
    }

    /**
     * Extract ID3 tags
     */
    private function extractId3Tags(string $content): array
    {
        $tags = [];

        // Check for ID3v2 header
        if (str_starts_with($content, 'ID3')) {
            $version = ord($content[3]);
            $revision = ord($content[4]);
            $tags['id3v2'] = [
                'version' => "2.{$version}.{$revision}",
                'size' => $this->extractId3Size(substr($content, 6, 4)),
            ];
        }

        // Look for ID3v1 tag at the end
        if (strlen($content) >= 128) {
            $id3v1Pos = strlen($content) - 128;
            if (substr($content, $id3v1Pos, 3) === 'TAG') {
                $tags['id3v1'] = [
                    'title' => trim(substr($content, $id3v1Pos + 3, 30)),
                    'artist' => trim(substr($content, $id3v1Pos + 33, 30)),
                    'album' => trim(substr($content, $id3v1Pos + 63, 30)),
                    'year' => trim(substr($content, $id3v1Pos + 93, 4)),
                ];
            }
        }

        return $tags;
    }

    /**
     * Parse WAV fmt chunk
     */
    private function parseWavFmtChunk(string $content, int $fmtPos): array
    {
        // Skip "fmt " signature and get chunk size
        $chunkSize = unpack('V', substr($content, $fmtPos + 4, 4))[1];

        if ($chunkSize < 16) {
            return [];
        }

        $fmtData = substr($content, $fmtPos + 8, $chunkSize);

        return [
            'audioFormat' => unpack('v', substr($fmtData, 0, 2))[1],
            'numChannels' => unpack('v', substr($fmtData, 2, 2))[1],
            'sampleRate' => unpack('V', substr($fmtData, 4, 4))[1],
            'byteRate' => unpack('V', substr($fmtData, 8, 4))[1],
            'blockAlign' => unpack('v', substr($fmtData, 12, 2))[1],
            'bitsPerSample' => unpack('v', substr($fmtData, 14, 2))[1],
        ];
    }

    /**
     * Extract ID3 size (synchsafe integer)
     */
    private function extractId3Size(string $sizeBytes): int
    {
        $bytes = array_map('ord', str_split($sizeBytes));

        return ($bytes[0] << 21) + ($bytes[1] << 14) + ($bytes[2] << 7) + $bytes[3];
    }
}