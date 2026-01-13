<?php

declare(strict_types=1);

namespace Langfuse\Integration\Laravel\Services;

use Langfuse\Client\Contracts\LangfuseClientInterface;

/**
 * Service for handling application shutdown and flushing spans
 */
class ShutdownHandlerService
{
    public function __construct(
        private readonly LangfuseClientInterface $langfuse
    ) {
    }

    /**
     * Flush all pending spans
     */
    public function flush(): void
    {
        try {
            $this->langfuse->flush();
        } catch (\Throwable $e) {
            // Silently handle errors during flush
            if (config('langfuse.debug', false)) {
                report($e);
            }
        }
    }

    /**
     * Shutdown the tracer
     */
    public function shutdown(): void
    {
        try {
            $this->langfuse->shutdown();
        } catch (\Throwable $e) {
            // Silently handle errors during shutdown
            if (config('langfuse.debug', false)) {
                report($e);
            }
        }
    }
}
