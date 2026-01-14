<?php

declare(strict_types=1);

namespace Langfuse\Integration\Prism;

use Illuminate\Container\Container;
use Illuminate\Support\ServiceProvider;
use Langfuse\Client\Contracts\LangfuseClientInterface;
use Langfuse\Integration\Prism\Contracts\PrismRequestExtractorInterface;
use Langfuse\Integration\Prism\Contracts\PrismResponseExtractorInterface;
use Langfuse\Integration\Prism\Services\PrismMetadataExtractor;
use Langfuse\Integration\Prism\Services\PrismRequestExtractor;
use Langfuse\Integration\Prism\Services\PrismResponseExtractor;
use Langfuse\Integration\Prism\Services\PrismTracingService;

// NOTE: PrismManager is NOT imported at class level to avoid autoloading
// when Prism is not installed. It's referenced via fully qualified class name.

/**
 * Service provider for Prism-Langfuse integration.
 *
 * This provider registers all necessary services for tracing Prism AI operations
 * with Langfuse. It extends the PrismManager to wrap providers with tracing.
 */
class PrismLangfuseServiceProvider extends ServiceProvider
{
    /**
     * All of the container singletons that should be registered.
     *
     * @var array<string, string>
     */
    public array $singletons = [
        PrismMetadataExtractor::class => PrismMetadataExtractor::class,
    ];

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        // Early exit if Prism is not installed - prevents autoload errors
        if (! $this->isPrismInstalled()) {
            return;
        }

        // Early exit if Prism integration is explicitly disabled
        if (! $this->isPrismIntegrationEnabled()) {
            return;
        }

        $this->registerExtractors();
        $this->registerTracingService();
        $this->registerEventHandler();
        $this->extendPrismManager();
    }

    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        // Prism integration is automatically enabled via PrismManager extension
        // No additional boot logic needed
    }

    /**
     * Check if Prism package is installed.
     */
    private function isPrismInstalled(): bool
    {
        return class_exists('Prism\Prism\PrismManager');
    }

    /**
     * Check if Prism integration is enabled in configuration.
     */
    private function isPrismIntegrationEnabled(): bool
    {
        // Default to enabled if not explicitly set
        return config('langfuse.prism.enabled', true);
    }

    /**
     * Register request and response extractors.
     */
    private function registerExtractors(): void
    {
        // Register interface → implementation bindings
        $this->app->singleton(
            PrismRequestExtractorInterface::class,
            PrismRequestExtractor::class
        );

        $this->app->singleton(
            PrismResponseExtractorInterface::class,
            PrismResponseExtractor::class
        );

        // Note: alias() signature is alias($abstract, $alias)
        // So alias(A, B) means "when B is requested, resolve A"
        // We want PrismRequestExtractor to resolve to PrismRequestExtractorInterface
        $this->app->alias(
            PrismRequestExtractorInterface::class,  // abstract (what to resolve)
            'langfuse.prism.request_extractor'      // alias name
        );

        $this->app->alias(
            PrismResponseExtractorInterface::class, // abstract (what to resolve)
            'langfuse.prism.response_extractor'     // alias name
        );
    }

    /**
     * Register the Prism tracing service.
     */
    private function registerTracingService(): void
    {
        $this->app->singleton(PrismTracingService::class, function (Container $app) {
            return new PrismTracingService(
                langfuse: $app->make(LangfuseClientInterface::class),
                requestExtractor: $app->make(PrismRequestExtractorInterface::class),
                responseExtractor: $app->make(PrismResponseExtractorInterface::class),
                metadataExtractor: $app->make(PrismMetadataExtractor::class),
                traceModelParams: config('langfuse.prism.trace_model_params', true),
                traceUsage: config('langfuse.prism.trace_usage', true),
                traceCost: config('langfuse.prism.trace_cost', true),
            );
        });
    }

    /**
     * Register the Prism event handler for event-based tracing.
     */
    private function registerEventHandler(): void
    {
        $this->app->singleton(PrismEventHandler::class, function (Container $app) {
            return new PrismEventHandler(
                langfuse: $app->make(LangfuseClientInterface::class)
            );
        });
    }

    /**
     * Extend the PrismManager with Langfuse tracing wrapper.
     *
     * Uses lazy resolution via closures to avoid circular dependency issues.
     * Dependencies are only resolved when actually needed (when resolve() is called),
     * not during the extend() callback execution.
     */
    private function extendPrismManager(): void
    {
        // Only extend if auto_trace is enabled
        if (! config('langfuse.prism.auto_trace', true)) {
            return;
        }

        $this->app->extend(
            \Prism\Prism\PrismManager::class,
            function (\Prism\Prism\PrismManager $original, Container $app): LangfusePrismManager {
                // Use lazy resolvers (closures) to avoid resolving dependencies
                // during the extend callback - this prevents circular resolution
                return new LangfusePrismManager(
                    originalManager: $original,
                    langfuseResolver: fn () => $app->make(LangfuseClientInterface::class),
                    tracingServiceResolver: fn () => $app->make(PrismTracingService::class),
                );
            }
        );
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            PrismRequestExtractorInterface::class,
            PrismResponseExtractorInterface::class,
            PrismMetadataExtractor::class,
            PrismTracingService::class,
            PrismEventHandler::class,
        ];
    }
}
