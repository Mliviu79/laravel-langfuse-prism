<?php

declare(strict_types=1);

namespace Langfuse\Media\Processors;

use Langfuse\Media\Contracts\MediaProcessorInterface;
use Langfuse\Support\Enums\MediaContentType;

/**
 * Document media processor
 */
class DocumentProcessor implements MediaProcessorInterface
{
    public function canProcess(MediaContentType $contentType): bool
    {
        return $contentType->isDocument();
    }

    public function process(mixed $content, MediaContentType $contentType): array
    {
        if (!is_string($content)) {
            throw new \InvalidArgumentException('Document content must be a string');
        }

        $metadata = [
            'contentType' => $contentType->value,
            'size' => strlen($content),
        ];

        try {
            $documentInfo = match ($contentType) {
                MediaContentType::DOCUMENT_PDF => $this->processPdf($content),
                MediaContentType::DOCUMENT_TXT => $this->processText($content),
                MediaContentType::DOCUMENT_JSON => $this->processJson($content),
                MediaContentType::DOCUMENT_XML => $this->processXml($content),
                MediaContentType::DOCUMENT_CSV => $this->processCsv($content),
                default => [],
            };

            $metadata = array_merge($metadata, $documentInfo);
        } catch (\Throwable $e) {
            $metadata['processing_error'] = $e->getMessage();
        }

        return $metadata;
    }

    public function getSupportedContentTypes(): array
    {
        return MediaContentType::getDocumentTypes();
    }

    public function getName(): string
    {
        return 'document-processor';
    }

    /**
     * Process PDF document
     */
    private function processPdf(string $content): array
    {
        $metadata = [];

        // Try to extract basic PDF info
        if (str_contains($content, '%PDF-')) {
            $metadata['format'] = 'pdf';

            // Extract PDF version
            if (preg_match('/%PDF-(\d\.\d)/', $content, $matches)) {
                $metadata['version'] = $matches[1];
            }

            // Try to count pages (simple heuristic)
            $pageCount = substr_count($content, '/Type /Page');
            if ($pageCount > 0) {
                $metadata['pageCount'] = $pageCount;
            }

            // Extract title if available
            if (preg_match('/\/Title\s*\(([^)]+)\)/', $content, $matches)) {
                $metadata['title'] = trim($matches[1]);
            }

            // Extract author if available
            if (preg_match('/\/Author\s*\(([^)]+)\)/', $content, $matches)) {
                $metadata['author'] = trim($matches[1]);
            }
        }

        return $metadata;
    }

    /**
     * Process text document
     */
    private function processText(string $content): array
    {
        return [
            'format' => 'text',
            'lineCount' => substr_count($content, "\n") + 1,
            'wordCount' => str_word_count($content),
            'characterCount' => mb_strlen($content),
            'encoding' => mb_detect_encoding($content, ['UTF-8', 'ASCII', 'ISO-8859-1'], true) ?: 'unknown',
        ];
    }

    /**
     * Process JSON document
     */
    private function processJson(string $content): array
    {
        $metadata = ['format' => 'json'];

        try {
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

            $metadata['valid'] = true;
            $metadata['depth'] = $this->getArrayDepth($decoded);

            if (is_array($decoded)) {
                $metadata['elementCount'] = count($decoded);
                $metadata['isAssociative'] = $this->isAssociativeArray($decoded);
            }
        } catch (\JsonException $e) {
            $metadata['valid'] = false;
            $metadata['error'] = $e->getMessage();
        }

        return $metadata;
    }

    /**
     * Process XML document
     */
    private function processXml(string $content): array
    {
        $metadata = ['format' => 'xml'];

        try {
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($content);

            if ($xml !== false) {
                $metadata['valid'] = true;
                $metadata['rootElement'] = $xml->getName();
                $metadata['elementCount'] = $this->countXmlElements($xml);

                // Get namespace information
                $namespaces = $xml->getNamespaces(true);
                if (!empty($namespaces)) {
                    $metadata['namespaces'] = array_keys($namespaces);
                }
            } else {
                $metadata['valid'] = false;
                $errors = libxml_get_errors();
                $metadata['errors'] = array_map(fn($error) => $error->message, $errors);
            }

            libxml_clear_errors();
        } catch (\Throwable $e) {
            $metadata['valid'] = false;
            $metadata['error'] = $e->getMessage();
        }

        return $metadata;
    }

    /**
     * Process CSV document
     */
    private function processCsv(string $content): array
    {
        $metadata = ['format' => 'csv'];

        try {
            $lines = str_getcsv($content, "\n");
            $metadata['rowCount'] = count($lines);

            $firstRow = str_getcsv($lines[0]);
            $metadata['columnCount'] = count($firstRow);

            // Detect delimiter
            $metadata['delimiter'] = $this->detectCsvDelimiter($content);

            // Check if first row might be headers
            $metadata['hasHeaders'] = $this->likelyHasHeaders($firstRow);
        } catch (\Throwable $e) {
            $metadata['error'] = $e->getMessage();
        }

        return $metadata;
    }

    /**
     * Get array depth
     */
    private function getArrayDepth(mixed $array): int
    {
        if (!is_array($array)) {
            return 0;
        }

        $maxDepth = 1;
        foreach ($array as $value) {
            if (is_array($value)) {
                $depth = 1 + $this->getArrayDepth($value);
                $maxDepth = max($maxDepth, $depth);
            }
        }

        return $maxDepth;
    }

    /**
     * Check if array is associative
     */
    private function isAssociativeArray(array $array): bool
    {
        return array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * Count XML elements recursively
     */
    private function countXmlElements(\SimpleXMLElement $xml): int
    {
        $count = 1; // Count the current element

        foreach ($xml->children() as $child) {
            $count += $this->countXmlElements($child);
        }

        return $count;
    }

    /**
     * Detect CSV delimiter
     */
    private function detectCsvDelimiter(string $content): string
    {
        $delimiters = [',', ';', '\t', '|'];
        $counts = [];

        foreach ($delimiters as $delimiter) {
            $counts[$delimiter] = substr_count($content, $delimiter);
        }

        return array_search(max($counts), $counts, true) ?: ',';
    }

    /**
     * Check if first row likely contains headers
     */
    private function likelyHasHeaders(array $firstRow): bool
    {
        foreach ($firstRow as $value) {
            // If any value looks like a number, probably not headers
            if (is_numeric($value)) {
                return false;
            }
        }

        return true;
    }
}