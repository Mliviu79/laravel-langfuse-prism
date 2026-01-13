<?php

declare(strict_types=1);

namespace Langfuse\Integration\Laravel\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Langfuse\Observability\Contracts\SpanInterface;

/**
 * Event fired when a span is started
 */
class SpanStarted
{
    use Dispatchable;

    public function __construct(
        public readonly SpanInterface $span
    ) {
    }
}
