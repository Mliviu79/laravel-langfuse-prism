<?php

declare(strict_types=1);

namespace Langfuse\Integration\Laravel\Queue\Contracts;

/**
 * Interface for queue operations
 */
interface QueueServiceInterface
{
    /**
     * Dispatch a job to the queue
     */
    public function dispatch(mixed $job): void;

    /**
     * Check if queue should be used (sync vs async)
     */
    public function shouldUseQueue(): bool;
}
