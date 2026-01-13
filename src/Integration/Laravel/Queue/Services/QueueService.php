<?php

declare(strict_types=1);

namespace Langfuse\Integration\Laravel\Queue\Services;

use Illuminate\Contracts\Queue\Queue;
use Langfuse\Integration\Laravel\Queue\Contracts\QueueServiceInterface;
use Langfuse\Integration\Laravel\Queue\Jobs\FlushSpansJob;

/**
 * Service for queue operations
 */
class QueueService implements QueueServiceInterface
{
    public function __construct(
        private readonly Queue $queue
    ) {
    }

    /**
     * Dispatch a job to the queue
     */
    public function dispatch(mixed $job): void
    {
        $this->queue->push($job);
    }

    /**
     * Check if queue should be used (sync vs async)
     */
    public function shouldUseQueue(): bool
    {
        return config('langfuse.queue.enabled', false)
            && config('queue.default') !== 'sync';
    }

    /**
     * Dispatch flush spans job
     */
    public function dispatchFlushSpans(): void
    {
        if ($this->shouldUseQueue()) {
            $this->dispatch(new FlushSpansJob());
        }
    }
}
