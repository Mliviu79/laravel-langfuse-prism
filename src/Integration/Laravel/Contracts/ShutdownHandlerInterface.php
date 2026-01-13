<?php

declare(strict_types=1);

namespace Langfuse\Integration\Laravel\Contracts;

/**
 * Interface for shutdown handlers
 */
interface ShutdownHandlerInterface
{
    /**
     * Flush all pending spans
     */
    public function flush(): void;

    /**
     * Shutdown the tracer
     */
    public function shutdown(): void;
}
