<?php

declare(strict_types=1);

namespace Langfuse\Support\Traits;

use Langfuse\Support\Contracts\IdGeneratorInterface;
use Ramsey\Uuid\Uuid;

/**
 * Trait for generating unique IDs.
 *
 * This trait provides convenient methods for generating unique identifiers.
 * It can use a provided IdGeneratorInterface implementation or fall back
 * to generating UUIDs directly if no generator is available.
 *
 * Classes using this trait should implement the `getIdGenerator()` method
 * if they want to use a custom ID generator, or the trait will use
 * Ramsey's UUID library directly.
 */
trait GeneratesIds
{
    /**
     * Get the ID generator instance if available.
     *
     * Override this method to provide a custom ID generator.
     */
    protected function getIdGenerator(): ?IdGeneratorInterface
    {
        // Check if idGenerator property exists
        if (property_exists($this, 'idGenerator') && $this->idGenerator instanceof IdGeneratorInterface) {
            return $this->idGenerator;
        }

        // Try to resolve from container if available
        if (function_exists('app') && app()->has(IdGeneratorInterface::class)) {
            return app(IdGeneratorInterface::class);
        }

        return null;
    }

    /**
     * Generate a unique ID.
     */
    protected function generateId(): string
    {
        $generator = $this->getIdGenerator();

        if ($generator !== null) {
            return $generator->generateId();
        }

        return Uuid::uuid4()->toString();
    }

    /**
     * Generate a unique trace ID.
     */
    protected function generateTraceId(): string
    {
        $generator = $this->getIdGenerator();

        if ($generator !== null) {
            return $generator->generateTraceId();
        }

        return $this->generateId();
    }

    /**
     * Generate a unique observation ID.
     */
    protected function generateObservationId(): string
    {
        $generator = $this->getIdGenerator();

        if ($generator !== null) {
            return $generator->generateObservationId();
        }

        return $this->generateId();
    }

    /**
     * Generate a unique score ID.
     */
    protected function generateScoreId(): string
    {
        $generator = $this->getIdGenerator();

        if ($generator !== null) {
            return $generator->generateScoreId();
        }

        return $this->generateId();
    }

    /**
     * Generate a timestamp in ISO 8601 format.
     */
    protected function generateTimestamp(): string
    {
        $generator = $this->getIdGenerator();

        if ($generator !== null && method_exists($generator, 'generateTimestamp')) {
            return $generator->generateTimestamp();
        }

        return now()->toISOString();
    }

    /**
     * Check if a string is a valid UUID.
     */
    protected function isValidUuid(string $uuid): bool
    {
        $generator = $this->getIdGenerator();

        if ($generator !== null && method_exists($generator, 'isValidUuid')) {
            return $generator->isValidUuid($uuid);
        }

        return Uuid::isValid($uuid);
    }
}
