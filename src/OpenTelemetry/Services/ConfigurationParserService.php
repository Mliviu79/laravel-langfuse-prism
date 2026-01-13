<?php

declare(strict_types=1);

namespace Langfuse\OpenTelemetry\Services;

use InvalidArgumentException;
use Langfuse\OpenTelemetry\DTOs\OpenTelemetryConfigDto;
use Langfuse\Support\Exceptions\ConfigurationException;

/**
 * Service for parsing and validating OpenTelemetry configuration
 */
class ConfigurationParserService
{
    public function __construct(
        private readonly EnvironmentService $environmentService
    ) {
    }

    /**
     * Parse configuration from Laravel config array
     */
    public function parseFromLaravelConfig(array $config): OpenTelemetryConfigDto
    {
        $headers = $this->buildHeaders($config);

        return new OpenTelemetryConfigDto(
            endpoint: $config['otel_endpoint'] ?? $this->getDefaultEndpoint(),
            protocol: $config['otel_protocol'] ?? $this->environmentService->getEnvValue('OTEL_EXPORTER_OTLP_PROTOCOL', 'http/json'),
            headers: $headers,
            timeout: $config['otel_timeout'] ?? (int) $this->environmentService->getEnvValue('OTEL_EXPORTER_OTLP_TIMEOUT', '5000'),
            maxQueueSize: $config['otel_max_queue_size'] ?? (int) $this->environmentService->getEnvValue('OTEL_BSP_MAX_QUEUE_SIZE', '2048'),
            scheduledDelayMillis: $config['otel_schedule_delay'] ?? (int) $this->environmentService->getEnvValue('OTEL_BSP_SCHEDULE_DELAY', '1000'),
            exportTimeoutMillis: $config['otel_export_timeout'] ?? (int) $this->environmentService->getEnvValue('OTEL_BSP_EXPORT_TIMEOUT', '30000'),
            maxExportBatchSize: $config['otel_max_export_batch_size'] ?? (int) $this->environmentService->getEnvValue('OTEL_BSP_MAX_EXPORT_BATCH_SIZE', '512'),
            compression: $config['otel_compression'] ?? $this->environmentService->getBoolEnvValue('OTEL_EXPORTER_OTLP_COMPRESSION', false),
            serviceName: $config['service_name'] ?? $this->environmentService->getEnvValue('OTEL_SERVICE_NAME', 'langfuse-php-sdk'),
            serviceVersion: $config['service_version'] ?? $this->environmentService->getEnvValue('OTEL_SERVICE_VERSION', '1.0.0'),
            resourceAttributes: $config['otel_resource_attributes'] ?? $this->parseResourceAttributes(),
            useSimpleProcessor: $config['otel_use_simple_processor'] ?? $this->environmentService->getBoolEnvValue('OTEL_USE_SIMPLE_PROCESSOR', $this->environmentService->isRunningInConsole()),
        );
    }

    /**
     * Build headers from configuration
     */
    private function buildHeaders(array $config): array
    {
        $headers = [];
        $headersEnv = $this->parseHeaders($config['otel_headers'] ?? []);
        $useCollector = $config['otel_use_collector'] ?? false;

        // If basic auth keys are provided and we are NOT using a collector, add Basic Auth
        if (!$useCollector && isset($config['public_key']) && isset($config['secret_key'])) {
            $headers['Authorization'] = 'Basic '.base64_encode($config['public_key'].':'.$config['secret_key']);
        }

        // Merge env headers (which might contain custom Authorization)
        return array_merge($headers, $headersEnv);
    }

    /**
     * Parse headers from array or string
     */
    private function parseHeaders(array|string $headersInput): array
    {
        if (is_array($headersInput)) {
            return $headersInput;
        }

        $headers = [];
        $pairs = explode(',', $headersInput);

        foreach ($pairs as $pair) {
            $parts = explode('=', trim($pair), 2);
            if (count($parts) === 2) {
                $headers[trim($parts[0])] = trim($parts[1]);
            }
        }

        return $headers;
    }

    /**
     * Parse resource attributes from environment variable
     */
    private function parseResourceAttributes(): array
    {
        $attributesEnv = $this->environmentService->getEnvValue('OTEL_RESOURCE_ATTRIBUTES');
        if (!$attributesEnv) {
            return [];
        }

        $attributes = [];
        $pairs = explode(',', $attributesEnv);

        foreach ($pairs as $pair) {
            $parts = explode('=', trim($pair), 2);
            if (count($parts) === 2) {
                $attributes[trim($parts[0])] = trim($parts[1]);
            }
        }

        return $attributes;
    }

    /**
     * Get default endpoint
     */
    private function getDefaultEndpoint(): string
    {
        return $this->environmentService->getEnvValue('OTEL_EXPORTER_OTLP_ENDPOINT', 'https://cloud.langfuse.com/api/public/otel');
    }

    /**
     * Validate configuration DTO
     */
    public function validate(OpenTelemetryConfigDto $config): void
    {
        if (empty($config->endpoint)) {
            throw ConfigurationException::missingRequiredValue('otel_endpoint');
        }

        if (!filter_var($config->endpoint, FILTER_VALIDATE_URL)) {
            throw ConfigurationException::invalidUrl($config->endpoint);
        }

        if (!in_array($config->protocol, ['http/json', 'http/protobuf', 'grpc'])) {
            throw ConfigurationException::invalidValue('otel_protocol', "Must be one of: http/json, http/protobuf, grpc");
        }

        if ($config->timeout <= 0) {
            throw new InvalidArgumentException('Timeout must be a positive integer.');
        }

        if ($config->maxQueueSize <= 0) {
            throw new InvalidArgumentException('Max queue size must be a positive integer.');
        }

        if ($config->scheduledDelayMillis <= 0) {
            throw new InvalidArgumentException('Scheduled delay must be a positive integer.');
        }

        if ($config->exportTimeoutMillis <= 0) {
            throw new InvalidArgumentException('Export timeout must be a positive integer.');
        }

        if ($config->maxExportBatchSize <= 0) {
            throw new InvalidArgumentException('Max export batch size must be a positive integer.');
        }
    }
}
