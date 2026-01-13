<?php

declare(strict_types=1);

namespace Langfuse\Observability\Contracts;

/**
 * Contract for dispatching span lifecycle events.
 *
 * This allows the observability layer to dispatch events without
 * depending directly on Laravel's event system.
 */
interface EventDispatcherInterface
{
    /**
     * Dispatch a span started event.
     */
    public function dispatchSpanStarted(SpanInterface $span): void;

    /**
     * Dispatch a span ended event.
     */
    public function dispatchSpanEnded(SpanInterface $span): void;

    /**
     * Dispatch a trace completed event.
     */
    public function dispatchTraceCompleted(string $traceId): void;
}
