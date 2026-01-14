<?php

declare(strict_types=1);

namespace Langfuse\OpenTelemetry\Services;

use Langfuse\OpenTelemetry\Contracts\SpanExporterFactoryInterface;
use Langfuse\OpenTelemetry\Contracts\TracerProviderFactoryInterface;
use Langfuse\OpenTelemetry\DTOs\OpenTelemetryConfigDto;
use OpenTelemetry\API\Common\Time\Clock;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Resource\ResourceInfoFactory;
use OpenTelemetry\SDK\Trace\SpanProcessor\BatchSpanProcessor;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;
use OpenTelemetry\SDK\Trace\TracerProviderInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Factory for creating TracerProvider instances
 */
class TracerProviderFactory implements TracerProviderFactoryInterface
{
    public function __construct(
        private readonly SpanExporterFactoryInterface $spanExporterFactory
    ) {
    }

    public function create(OpenTelemetryConfigDto $config, ?LoggerInterface $logger = null): TracerProviderInterface
    {
        $logger = $logger ?? new NullLogger();

        // Create resource with service information
        $resource = ResourceInfo::create(Attributes::create([
            'service.name' => $config->serviceName,
            'service.version' => $config->serviceVersion,
            'telemetry.sdk.language' => 'php',
            'telemetry.sdk.name' => 'langfuse-php',
            ...$config->resourceAttributes,
        ]))->merge(ResourceInfoFactory::emptyResource());

        // Create OTLP exporter
        $exporter = $this->spanExporterFactory->create($config, $logger);

        // Create span processor based on context:
        // - SimpleProcessor: For CLI/queue jobs - exports immediately (blocking but reliable)
        // - BatchProcessor: For HTTP requests - batches in background (non-blocking)
        if ($config->useSimpleProcessor) {
            // SimpleSpanProcessor: Exports each span immediately when it ends
            // - Blocks until export completes (~50ms)
            // - Perfect for queue jobs where reliability > performance
            // - Ensures spans are exported before job terminates
            $processor = new SimpleSpanProcessor($exporter);
        } else {
            // BatchSpanProcessor: Accumulates spans and exports in batches
            // - Non-blocking: Adds span to queue and returns immediately
            // - Exports in background every N ms or when batch fills up
            // - Perfect for HTTP requests where latency matters
            $processor = new BatchSpanProcessor(
                $exporter,
                Clock::getDefault(),
                $config->maxQueueSize,
                $config->scheduledDelayMillis,
                $config->exportTimeoutMillis,
                $config->maxExportBatchSize
            );
        }

        // Create and return tracer provider
        return TracerProvider::builder()
            ->addSpanProcessor($processor)
            ->setResource($resource)
            ->build();
    }
}
