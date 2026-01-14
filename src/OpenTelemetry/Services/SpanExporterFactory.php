<?php

declare(strict_types=1);

namespace Langfuse\OpenTelemetry\Services;

use Langfuse\OpenTelemetry\Contracts\SpanExporterFactoryInterface;
use Langfuse\OpenTelemetry\DTOs\OpenTelemetryConfigDto;
use OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory;
use OpenTelemetry\Contrib\Otlp\SpanExporter;
use OpenTelemetry\SDK\Trace\SpanExporterInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Factory for creating SpanExporter instances
 */
class SpanExporterFactory implements SpanExporterFactoryInterface
{
    public function create(OpenTelemetryConfigDto $config, ?LoggerInterface $logger = null): SpanExporterInterface
    {
        $logger = $logger ?? new NullLogger;

        // Ensure endpoint includes the traces path
        $endpoint = rtrim($config->endpoint, '/');
        if (! str_ends_with($endpoint, '/v1/traces')) {
            $endpoint .= '/v1/traces';
        }

        // Determine content type based on protocol
        $contentType = $config->protocol === 'http/protobuf' ? 'application/x-protobuf' : 'application/json';

        // Create transport using factory
        $transportFactory = new OtlpHttpTransportFactory;
        $transport = $transportFactory->create(
            endpoint: $endpoint,
            contentType: $contentType,
            headers: $config->headers,
            compression: $config->compression ? 'gzip' : null,
            timeout: $config->timeout / 1000, // Convert milliseconds to seconds
            retryDelay: 100,
            maxRetries: 3,
        );

        return new SpanExporter($transport);
    }
}
