<?php

declare(strict_types=1);

namespace Langfuse\OpenTelemetry\Contracts;

use OpenTelemetry\SDK\Trace\SpanExporterInterface;
use Psr\Log\LoggerInterface;

/**
 * Interface for creating SpanExporter instances
 */
interface SpanExporterFactoryInterface
{
    /**
     * Create a SpanExporter from configuration DTO
     */
    public function create(\Langfuse\OpenTelemetry\DTOs\OpenTelemetryConfigDto $config, ?LoggerInterface $logger = null): SpanExporterInterface;
}
