<?php

declare(strict_types=1);

namespace Langfuse\Observability\Services;

use Langfuse\Observability\Contracts\SpanInterface;
use Langfuse\Observability\Contracts\SpanManagerInterface;

/**
 * Service for managing active spans
 */
class SpanManager implements SpanManagerInterface
{
    /**
     * @var array<string, SpanInterface>
     */
    private array $spans = [];

    /**
     * Register an active span
     */
    public function registerSpan(string $spanId, SpanInterface $span): void
    {
        $this->spans[$spanId] = $span;
    }

    /**
     * Get an active span by ID
     */
    public function getSpan(string $spanId): ?SpanInterface
    {
        return $this->spans[$spanId] ?? null;
    }

    /**
     * Remove a span from active spans
     */
    public function removeSpan(string $spanId): void
    {
        unset($this->spans[$spanId]);
    }

    /**
     * Check if a span is registered
     */
    public function hasSpan(string $spanId): bool
    {
        return isset($this->spans[$spanId]);
    }

    /**
     * Get all active spans
     *
     * @return array<string, SpanInterface>
     */
    public function getAllSpans(): array
    {
        return $this->spans;
    }

    /**
     * Clear all active spans
     */
    public function clear(): void
    {
        $this->spans = [];
    }
}
