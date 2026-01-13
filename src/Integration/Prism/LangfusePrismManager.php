<?php

declare(strict_types=1);

namespace Langfuse\Integration\Prism;

use Closure;
use Langfuse\Client\Contracts\LangfuseClientInterface;
use Langfuse\Integration\Prism\Services\PrismTracingService;
use Prism\Prism\Enums\Provider as ProviderEnum;
use Prism\Prism\PrismManager;
use Prism\Prism\Providers\Provider;

/**
 * Decorator for PrismManager that wraps all providers with Langfuse tracing.
 * 
 * Uses lazy loading for dependencies to avoid circular resolution issues
 * with Laravel's container extend() mechanism.
 */
class LangfusePrismManager extends PrismManager
{
    private ?LangfuseClientInterface $resolvedLangfuse = null;
    private ?PrismTracingService $resolvedTracingService = null;

    /**
     * @param PrismManager $originalManager The original PrismManager instance
     * @param Closure $langfuseResolver Lazy resolver for LangfuseClientInterface
     * @param Closure $tracingServiceResolver Lazy resolver for PrismTracingService
     */
    public function __construct(
        private readonly PrismManager $originalManager,
        private readonly Closure $langfuseResolver,
        private readonly Closure $tracingServiceResolver,
    ) {
        // Don't call parent::__construct() - we delegate to $originalManager
    }

    /**
     * @param  array<string, mixed>  $providerConfig
     */
    public function resolve(ProviderEnum|string $name, array $providerConfig = []): Provider
    {
        // Delegate to the original manager to get the actual provider
        $provider = $this->originalManager->resolve($name, $providerConfig);

        // Wrap the provider with Langfuse tracing if enabled
        if ($this->shouldTrace()) {
            return new Providers\LangfuseTracingProvider(
                provider: $provider,
                tracingService: $this->getTracingService(),
            );
        }

        return $provider;
    }

    /**
     * Lazily resolve the LangfuseClientInterface.
     */
    private function getLangfuse(): LangfuseClientInterface
    {
        if ($this->resolvedLangfuse === null) {
            $this->resolvedLangfuse = ($this->langfuseResolver)();
        }
        return $this->resolvedLangfuse;
    }

    /**
     * Lazily resolve the PrismTracingService.
     */
    private function getTracingService(): PrismTracingService
    {
        if ($this->resolvedTracingService === null) {
            $this->resolvedTracingService = ($this->tracingServiceResolver)();
        }
        return $this->resolvedTracingService;
    }

    private function shouldTrace(): bool
    {
        return config('langfuse.tracing_enabled', true)
            && config('langfuse.prism.auto_trace', true);
    }

    /**
     * Forward extend() calls to the original manager.
     */
    public function extend(string $name, Closure $callback): static
    {
        $this->originalManager->extend($name, $callback);
        return $this;
    }

    /**
     * Forward all other method calls to the original manager.
     */
    public function __call(string $method, array $arguments): mixed
    {
        return $this->originalManager->{$method}(...$arguments);
    }
}
