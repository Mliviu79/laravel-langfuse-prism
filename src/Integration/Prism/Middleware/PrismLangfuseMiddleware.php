<?php

declare(strict_types=1);

namespace Langfuse\Integration\Prism\Middleware;

use Closure;
use DateTime;
use Langfuse\Integration\Prism\Services\PrismTracingService;
use Throwable;

/**
 * Prism middleware for automatic Langfuse tracing of LLM operations
 * Refactored to use PrismTracingService and extractors
 */
class PrismLangfuseMiddleware
{
    public function __construct(
        private readonly PrismTracingService $tracingService
    ) {
    }

    /**
     * Handle the Prism request
     */
    public function handle(mixed $request, Closure $next): mixed
    {
        if (!$this->shouldTrace()) {
            return $next($request);
        }

        $startTime = new DateTime();
        $span = $this->tracingService->startTrace($request, 'middleware');

        try {
            $response = $next($request);
            $this->tracingService->updateWithSuccess($span, $response, $startTime);

            return $response;
        } catch (Throwable $e) {
            $this->tracingService->updateWithError($span, $e);
            throw $e;
        } finally {
            $span->end();
        }
    }

    /**
     * Check if we should trace this request
     */
    private function shouldTrace(): bool
    {
        return config('langfuse.tracing_enabled', true)
            && config('langfuse.prism.auto_trace', true);
    }
}
