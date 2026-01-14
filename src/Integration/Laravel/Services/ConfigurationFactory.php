<?php

declare(strict_types=1);

namespace Langfuse\Integration\Laravel\Services;

use Langfuse\Client\Configuration;
use Langfuse\OpenTelemetry\DTOs\OpenTelemetryConfigDto;
use Langfuse\OpenTelemetry\Services\ConfigurationParserService;

/**
 * Factory for creating configuration objects from Laravel config
 */
class ConfigurationFactory
{
    public function __construct(
        private readonly ConfigurationParserService $configParser
    ) {}

    /**
     * Create Langfuse Configuration from Laravel config
     */
    public function createLangfuseConfig(array $config): Configuration
    {
        return Configuration::fromLaravelConfig($config);
    }

    /**
     * Create OpenTelemetry Config DTO from Langfuse Configuration
     */
    public function createOpenTelemetryConfig(Configuration $config): OpenTelemetryConfigDto
    {
        $laravelConfig = [
            'public_key' => $config->publicKey,
            'secret_key' => $config->secretKey,
            'otel_endpoint' => $config->otelEndpoint,
            'otel_protocol' => $config->otelProtocol,
            'otel_use_collector' => $config->otelUseCollector,
            'otel_headers' => $config->otelHeaders,
            'service_name' => 'langfuse-php-sdk',
            'service_version' => '1.0.0',
            'otel_resource_attributes' => [
                'langfuse.environment' => $config->environment,
                'langfuse.release' => (string) ($config->release ?? 'unknown'),
            ],
        ];

        return $this->configParser->parseFromLaravelConfig($laravelConfig);
    }
}
