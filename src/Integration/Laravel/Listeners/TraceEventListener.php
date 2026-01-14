<?php

declare(strict_types=1);

namespace Langfuse\Integration\Laravel\Listeners;

use Langfuse\Integration\Laravel\Events\SpanEnded;
use Langfuse\Integration\Laravel\Events\SpanStarted;
use Langfuse\Integration\Laravel\Events\TraceCompleted;

/**
 * Event listener for Langfuse tracing events
 */
class TraceEventListener
{
    /**
     * Handle span started event
     */
    public function handleSpanStarted(SpanStarted $event): void
    {
        // Allow users to hook into span started events
        // This can be extended by users to add custom logic
    }

    /**
     * Handle span ended event
     */
    public function handleSpanEnded(SpanEnded $event): void
    {
        // Allow users to hook into span ended events
        // This can be extended by users to add custom logic
    }

    /**
     * Handle trace completed event
     */
    public function handleTraceCompleted(TraceCompleted $event): void
    {
        // Allow users to hook into trace completed events
        // This can be extended by users to add custom logic
    }

    /**
     * Register event listeners
     */
    public function subscribe($events): void
    {
        $events->listen(
            SpanStarted::class,
            [TraceEventListener::class, 'handleSpanStarted']
        );

        $events->listen(
            SpanEnded::class,
            [TraceEventListener::class, 'handleSpanEnded']
        );

        $events->listen(
            TraceCompleted::class,
            [TraceEventListener::class, 'handleTraceCompleted']
        );
    }
}
