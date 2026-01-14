<?php

declare(strict_types=1);

namespace Langfuse\OpenTelemetry\Wrappers;

use Illuminate\Container\Container;
use Langfuse\Client\Configuration;
use Langfuse\Integration\Laravel\Services\ConfigurationFactory;
use Langfuse\Observability\Contracts\EventDispatcherInterface;
use Langfuse\Observability\Contracts\SpanInterface;
use Langfuse\Observability\Contracts\TracerInterface;
use Langfuse\Observability\Services\AttributeMapperService;
use Langfuse\Observability\Services\ParentResolverService;
use Langfuse\OpenTelemetry\Contracts\TracerProviderFactoryInterface;
use Langfuse\Support\Contracts\IdGeneratorInterface;
use Langfuse\Support\Enums\ObservationType;
use Langfuse\Support\Enums\SpanLevel;

/**
 * Lazy tracer wrapper that defers TracerProvider creation until first use
 * This prevents blocking network calls during service registration
 */
class LazyTracerWrapper implements TracerInterface
{
    private ?TracerWrapper $tracer = null;

    public function __construct(
        private readonly Container $app,
        private readonly ConfigurationFactory $configFactory,
        private readonly Configuration $config,
        private readonly ?EventDispatcherInterface $eventDispatcher = null
    ) {}

    /**
     * Get or create the actual tracer wrapper
     */
    private function getTracer(): TracerInterface
    {
        if ($this->tracer === null) {
            try {
                $otelConfig = $this->configFactory->createOpenTelemetryConfig($this->config);
                $tracerProviderFactory = $this->app->make(TracerProviderFactoryInterface::class);
                $tracerProvider = $tracerProviderFactory->create($otelConfig);

                $this->tracer = new TracerWrapper(
                    tracerProvider: $tracerProvider,
                    spanManager: $this->app->make(\Langfuse\Observability\Contracts\SpanManagerInterface::class),
                    parentResolver: $this->app->make(ParentResolverService::class),
                    attributeMapper: $this->app->make(AttributeMapperService::class),
                    idGenerator: $this->app->make(IdGeneratorInterface::class),
                    eventDispatcher: $this->eventDispatcher,
                );
            } catch (\Throwable $e) {
                // If tracer creation fails, return null tracer
                if (function_exists('report')) {
                    report($e);
                }
                // Try to get IdGenerator from container, fallback to null if not available
                try {
                    $idGenerator = $this->app->make(IdGeneratorInterface::class);
                } catch (\Throwable) {
                    $idGenerator = null;
                }

                return new \Langfuse\Observability\Spans\NullTracer($idGenerator);
            }
        }

        return $this->tracer;
    }

    public function startSpan(
        string $name,
        ObservationType $type = ObservationType::SPAN,
        mixed $input = null,
        mixed $output = null,
        ?array $metadata = null,
        ?string $version = null,
        ?SpanLevel $level = null,
        ?string $statusMessage = null,
        ?string $parentId = null,
        ?string $model = null,
    ): SpanInterface {
        return $this->getTracer()->startSpan(
            name: $name,
            type: $type,
            input: $input,
            output: $output,
            metadata: $metadata,
            version: $version,
            level: $level,
            statusMessage: $statusMessage,
            parentId: $parentId,
            model: $model,
        );
    }

    public function getSpan(string $spanId): ?SpanInterface
    {
        return $this->getTracer()->getSpan($spanId);
    }

    public function removeSpan(string $spanId): void
    {
        $this->getTracer()->removeSpan($spanId);
    }

    public function flush(): void
    {
        if ($this->tracer !== null) {
            $this->tracer->flush();
        }
    }

    public function shutdown(): void
    {
        if ($this->tracer !== null) {
            $this->tracer->shutdown();
        }
    }
}
