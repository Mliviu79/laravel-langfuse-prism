<?php

declare(strict_types=1);

namespace Langfuse\Observability\Contracts;

/**
 * Interface for managing active spans
 */
interface SpanManagerInterface
{
    /**
     * Register an active span
     */
    public function registerSpan(string $spanId, SpanInterface $span): void;

    /**
     * Get an active span by ID
     */
    public function getSpan(string $spanId): ?SpanInterface;

    /**
     * Remove a span from active spans
     */
    public function removeSpan(string $spanId): void;

    /**
     * Check if a span is registered
     */
    public function hasSpan(string $spanId): bool;

    /**
     * Get all active spans
     *
     * @return array<string, SpanInterface>
     */
    public function getAllSpans(): array;

    /**
     * Clear all active spans
     */
    public function clear(): void;
}
