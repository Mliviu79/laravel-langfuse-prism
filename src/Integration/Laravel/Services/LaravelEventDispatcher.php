<?php

declare(strict_types=1);

namespace Langfuse\Integration\Laravel\Services;

use Illuminate\Contracts\Events\Dispatcher;
use Langfuse\Integration\Laravel\Events\SpanEnded;
use Langfuse\Integration\Laravel\Events\SpanStarted;
use Langfuse\Integration\Laravel\Events\TraceCompleted;
use Langfuse\Observability\Contracts\EventDispatcherInterface;
use Langfuse\Observability\Contracts\SpanInterface;

/**
 * Laravel implementation of the event dispatcher.
 *
 * Bridges the observability layer to Laravel's event system.
 */
class LaravelEventDispatcher implements EventDispatcherInterface
{
    public function __construct(
        private readonly Dispatcher $events
    ) {
    }

    public function dispatchSpanStarted(SpanInterface $span): void
    {
        $this->events->dispatch(new SpanStarted($span));
    }

    public function dispatchSpanEnded(SpanInterface $span): void
    {
        $this->events->dispatch(new SpanEnded($span));
    }

    public function dispatchTraceCompleted(string $traceId): void
    {
        $this->events->dispatch(new TraceCompleted($traceId));
    }
}
