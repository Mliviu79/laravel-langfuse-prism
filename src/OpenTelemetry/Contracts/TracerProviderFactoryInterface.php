<?php

declare(strict_types=1);

namespace Langfuse\OpenTelemetry\Contracts;

use OpenTelemetry\SDK\Trace\TracerProvider;
use Psr\Log\LoggerInterface;

/**
 * Interface for creating TracerProvider instances
 */
interface TracerProviderFactoryInterface
{
    /**
     * Create a TracerProvider from configuration DTO
     */
    public function create(\Langfuse\OpenTelemetry\DTOs\OpenTelemetryConfigDto $config, ?LoggerInterface $logger = null): TracerProvider;
}
