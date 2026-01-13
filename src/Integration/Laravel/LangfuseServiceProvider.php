<?php

declare(strict_types=1);

namespace Langfuse\Integration\Laravel;

use Illuminate\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use Langfuse\Client\Configuration;
use Langfuse\Integration\Laravel\Services\ConfigurationFactory;
use Langfuse\Integration\Laravel\Services\LaravelEventDispatcher;
use Langfuse\Integration\Laravel\Services\ShutdownHandlerService;
use Langfuse\Integration\Laravel\Services\StatusReportService;
use Langfuse\Observability\Contracts\EventDispatcherInterface;
use Langfuse\OpenTelemetry\Services\ConfigurationParserService;
use Langfuse\OpenTelemetry\Services\EnvironmentService;

class LangfuseServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../../config/langfuse.php',
            'langfuse'
        );

        // Register all services with deferred closures (no eager resolution)
        $this->registerCoreServices();
        $this->registerTracingServices();
        $this->registerApiServices();
        $this->registerClientServices();

        // Register Prism integration if available
        if ($this->shouldRegisterPrismIntegration()) {
            $this->registerPrismIntegration();
        }
    }

    /**
     * Register core configuration services
     */
    private function registerCoreServices(): void
    {
        // Environment service
        $this->app->singleton(EnvironmentService::class);

        // Configuration parser
        $this->app->singleton(ConfigurationParserService::class, function (Container $app) {
            return new ConfigurationParserService(
                $app->make(EnvironmentService::class)
            );
        });

        // Configuration factory
        $this->app->singleton(ConfigurationFactory::class, function (Container $app) {
            return new ConfigurationFactory(
                $app->make(ConfigurationParserService::class)
            );
        });

        // Main configuration
        $this->app->singleton(Configuration::class, function (Container $app) {
            return Configuration::fromLaravelConfig($app->make('config')->get('langfuse', []));
        });
    }

    /**
     * Register tracing/observability services
     */
    private function registerTracingServices(): void
    {
        // ID Generator
        $this->app->singleton(
            \Langfuse\Support\Contracts\IdGeneratorInterface::class,
            \Langfuse\Support\Services\IdGeneratorService::class
        );

        // Span Manager
        $this->app->singleton(
            \Langfuse\Observability\Contracts\SpanManagerInterface::class,
            \Langfuse\Observability\Services\SpanManager::class
        );

        // Parent Resolver
        $this->app->singleton(\Langfuse\Observability\Services\ParentResolverService::class, function (Container $app) {
            return new \Langfuse\Observability\Services\ParentResolverService(
                $app->make(\Langfuse\Observability\Contracts\SpanManagerInterface::class)
            );
        });

        // Attribute Mapper
        $this->app->singleton(\Langfuse\Observability\Services\AttributeMapperService::class);

        // SpanExporter Factory
        $this->app->singleton(
            \Langfuse\OpenTelemetry\Contracts\SpanExporterFactoryInterface::class,
            \Langfuse\OpenTelemetry\Services\SpanExporterFactory::class
        );

        // TracerProvider Factory
        $this->app->singleton(
            \Langfuse\OpenTelemetry\Contracts\TracerProviderFactoryInterface::class,
            function (Container $app) {
                return new \Langfuse\OpenTelemetry\Services\TracerProviderFactory(
                    $app->make(\Langfuse\OpenTelemetry\Contracts\SpanExporterFactoryInterface::class)
                );
            }
        );

        // Event Dispatcher for span lifecycle events
        $this->app->singleton(EventDispatcherInterface::class, function (Container $app) {
            return new LaravelEventDispatcher(
                $app->make(Dispatcher::class)
            );
        });

        // Status Report Service
        $this->app->singleton(StatusReportService::class, function (Container $app) {
            return new StatusReportService(
                config: $app->make(Configuration::class),
                configFactory: $app->make(ConfigurationFactory::class)
            );
        });

        // Tracer - use appropriate tracer based on configuration
        $this->app->singleton(\Langfuse\Observability\Contracts\TracerInterface::class, function (Container $app) {
            $config = $app->make(Configuration::class);
            $idGenerator = $app->make(\Langfuse\Support\Contracts\IdGeneratorInterface::class);

            // If tracing is disabled, return null tracer
            if (!$config->isTracingEnabled()) {
                return new \Langfuse\Observability\Spans\NullTracer($idGenerator);
            }

            // Check for Keepsuit Laravel OpenTelemetry package - use it if available
            // This allows Langfuse to integrate with existing OTEL infrastructure
            if (class_exists('Keepsuit\LaravelOpenTelemetry\Facades\Tracer')) {
                return new \Langfuse\OpenTelemetry\Adapters\KeepsuitTracerAdapter($idGenerator);
            }

            // Check if our OTEL is explicitly disabled
            if ($app->make('config')->get('langfuse.otel_enabled') === false) {
                return new \Langfuse\Observability\Spans\NullTracer($idGenerator);
            }

            // Use lazy tracer wrapper to defer OTEL initialization
            return new \Langfuse\OpenTelemetry\Wrappers\LazyTracerWrapper(
                app: $app,
                configFactory: $app->make(ConfigurationFactory::class),
                config: $config,
                eventDispatcher: $app->make(EventDispatcherInterface::class)
            );
        });

        // Tracing Service
        $this->app->singleton(\Langfuse\Client\Services\TracingService::class, function (Container $app) {
            return new \Langfuse\Client\Services\TracingService(
                config: $app->make(Configuration::class),
                tracer: $app->make(\Langfuse\Observability\Contracts\TracerInterface::class),
                idGenerator: $app->make(\Langfuse\Support\Contracts\IdGeneratorInterface::class)
            );
        });
    }

    /**
     * Register API services
     */
    private function registerApiServices(): void
    {
        $this->app->singleton(\Langfuse\Api\Services\DataSanitizationService::class);

        $this->app->singleton(\Langfuse\Api\Services\HttpRequestService::class, function (Container $app) {
            return new \Langfuse\Api\Services\HttpRequestService(
                config: $app->make(Configuration::class),
                httpClient: $app->make(\Illuminate\Http\Client\Factory::class),
                sanitizationService: $app->make(\Langfuse\Api\Services\DataSanitizationService::class)
            );
        });

        $this->app->singleton(\Langfuse\Api\Services\ResponseHandlerService::class, function (Container $app) {
            return new \Langfuse\Api\Services\ResponseHandlerService(
                config: $app->make(Configuration::class),
                sanitizationService: $app->make(\Langfuse\Api\Services\DataSanitizationService::class)
            );
        });

        $this->app->singleton(\Langfuse\Api\Contracts\ApiClientInterface::class, function (Container $app) {
            return new \Langfuse\Api\Client(
                config: $app->make(Configuration::class),
                httpRequestService: $app->make(\Langfuse\Api\Services\HttpRequestService::class),
                responseHandlerService: $app->make(\Langfuse\Api\Services\ResponseHandlerService::class),
                sanitizationService: $app->make(\Langfuse\Api\Services\DataSanitizationService::class)
            );
        });
        $this->app->alias(\Langfuse\Api\Contracts\ApiClientInterface::class, \Langfuse\Api\Client::class);
    }

    /**
     * Register client services
     */
    private function registerClientServices(): void
    {
        $this->app->singleton(\Langfuse\Client\Services\DatasetService::class, function (Container $app) {
            return new \Langfuse\Client\Services\DatasetService(
                apiClient: $app->make(\Langfuse\Api\Contracts\ApiClientInterface::class),
                idGenerator: $app->make(\Langfuse\Support\Contracts\IdGeneratorInterface::class)
            );
        });

        $this->app->singleton(\Langfuse\Client\Services\ScoreService::class, function (Container $app) {
            return new \Langfuse\Client\Services\ScoreService(
                apiClient: $app->make(\Langfuse\Api\Contracts\ApiClientInterface::class),
                idGenerator: $app->make(\Langfuse\Support\Contracts\IdGeneratorInterface::class)
            );
        });

        $this->app->singleton(\Langfuse\Client\Contracts\LangfuseClientInterface::class, function (Container $app) {
            return new \Langfuse\Client\LangfuseClient(
                config: $app->make(Configuration::class),
                apiClient: $app->make(\Langfuse\Api\Contracts\ApiClientInterface::class),
                tracingService: $app->make(\Langfuse\Client\Services\TracingService::class),
                datasetService: $app->make(\Langfuse\Client\Services\DatasetService::class),
                scoreService: $app->make(\Langfuse\Client\Services\ScoreService::class),
            );
        });
        $this->app->alias(\Langfuse\Client\Contracts\LangfuseClientInterface::class, \Langfuse\Client\LangfuseClient::class);
        $this->app->alias(\Langfuse\Client\Contracts\LangfuseClientInterface::class, 'langfuse');

        // Shutdown handler
        $this->app->singleton(ShutdownHandlerService::class, function (Container $app) {
            return new ShutdownHandlerService(
                $app->make(\Langfuse\Client\Contracts\LangfuseClientInterface::class)
            );
        });
    }

    /**
     * Check if Prism integration should be registered
     */
    protected function shouldRegisterPrismIntegration(): bool
    {
        // Check config first - using array access instead of config() helper
        // to avoid issues during early registration
        $config = $this->app->make('config');
        if (!$config->get('langfuse.prism.auto_trace', true)) {
            return false;
        }

        // Then check if Prism is available
        return class_exists('Prism\Prism\Prism');
    }

    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../../config/langfuse.php' => config_path('langfuse.php'),
            ], 'langfuse-config');

            // Register commands
            $this->commands([
                Commands\LangfuseStatusCommand::class,
            ]);
        }

        // Register shutdown handler to flush spans
        $this->registerShutdownHandler();
    }

    /**
     * Register shutdown handler to flush Langfuse spans
     */
    protected function registerShutdownHandler(): void
    {
        $this->app->terminating(function () {
            try {
                $shutdownHandler = $this->app->make(ShutdownHandlerService::class);
                $shutdownHandler->flush();
            } catch (\Throwable $e) {
                // Silently handle errors during flush
                if (config('langfuse.debug', false)) {
                    report($e);
                }
            }
        });
    }

    /**
     * Register Prism integration
     */
    protected function registerPrismIntegration(): void
    {
        try {
            $providerClass = 'Langfuse\Integration\Prism\PrismLangfuseServiceProvider';

            if (class_exists($providerClass) && !$this->app->bound($providerClass)) {
                $this->app->register($providerClass);
            }
        } catch (\Throwable $e) {
            if (config('langfuse.debug', false)) {
                report($e);
            }
        }
    }
}
