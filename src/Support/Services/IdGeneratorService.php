<?php

declare(strict_types=1);

namespace Langfuse\Support\Services;

use Langfuse\Support\Contracts\IdGeneratorInterface;
use Ramsey\Uuid\Uuid;

/**
 * Service for generating unique IDs
 */
class IdGeneratorService implements IdGeneratorInterface
{
    /**
     * Generate a unique ID
     */
    public function generateId(): string
    {
        return Uuid::uuid4()->toString();
    }

    /**
     * Generate a unique trace ID
     */
    public function generateTraceId(): string
    {
        return $this->generateId();
    }

    /**
     * Generate a unique observation ID
     */
    public function generateObservationId(): string
    {
        return $this->generateId();
    }

    /**
     * Generate a unique score ID
     */
    public function generateScoreId(): string
    {
        return $this->generateId();
    }

    /**
     * Generate a timestamp in ISO 8601 format
     */
    public function generateTimestamp(): string
    {
        return now()->toISOString();
    }

    /**
     * Check if a string is a valid UUID
     */
    public function isValidUuid(string $uuid): bool
    {
        return Uuid::isValid($uuid);
    }
}
