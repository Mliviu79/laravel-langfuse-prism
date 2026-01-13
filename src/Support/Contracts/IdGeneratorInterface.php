<?php

declare(strict_types=1);

namespace Langfuse\Support\Contracts;

/**
 * Interface for ID generation services
 */
interface IdGeneratorInterface
{
    /**
     * Generate a unique ID
     */
    public function generateId(): string;

    /**
     * Generate a unique trace ID
     */
    public function generateTraceId(): string;

    /**
     * Generate a unique observation ID
     */
    public function generateObservationId(): string;

    /**
     * Generate a unique score ID
     */
    public function generateScoreId(): string;

    /**
     * Generate a timestamp in ISO 8601 format
     */
    public function generateTimestamp(): string;

    /**
     * Check if a string is a valid UUID
     */
    public function isValidUuid(string $uuid): bool;
}
