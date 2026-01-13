<?php

declare(strict_types=1);

namespace Langfuse\Integration\Laravel\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Event fired when a trace is completed
 */
class TraceCompleted
{
    use Dispatchable;

    public function __construct(
        public readonly string $traceId
    ) {
    }
}
