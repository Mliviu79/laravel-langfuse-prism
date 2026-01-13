<?php

declare(strict_types=1);

namespace Langfuse\Integration\Laravel\Services;

use Langfuse\Client\Configuration;
use Langfuse\Integration\Laravel\DTOs\StatusReportDto;
use Langfuse\OpenTelemetry\DTOs\OpenTelemetryConfigDto;

/**
 * Service for generating Langfuse status reports.
 *
 * Encapsulates the logic for gathering and formatting status information.
 */
class StatusReportService
{
    public function __construct(
        private readonly Configuration $config,
        private readonly ConfigurationFactory $configFactory
    ) {
    }

    /**
     * Generate a status report for the current configuration.
     */
    public function generateReport(): StatusReportDto
    {
        $otelConfig = $this->configFactory->createOpenTelemetryConfig($this->config);

        return new StatusReportDto(
            tracingEnabled: $this->config->tracingEnabled,
            environment: $this->config->environment,
            sampleRate: $this->config->sampleRate,
            hasPublicKey: !empty($this->config->publicKey),
            hasSecretKey: !empty($this->config->secretKey),
            otelConfig: $otelConfig,
            phpSapi: php_sapi_name() ?: 'unknown',
            runningInConsole: $this->isRunningInConsole(),
            warnings: $this->collectWarnings($otelConfig),
        );
    }

    /**
     * Check if we're running in console mode.
     */
    private function isRunningInConsole(): bool
    {
        if (function_exists('app') && method_exists(app(), 'runningInConsole')) {
            return app()->runningInConsole();
        }

        return php_sapi_name() === 'cli';
    }

    /**
     * Collect any configuration warnings.
     *
     * @return string[]
     */
    private function collectWarnings(OpenTelemetryConfigDto $otelConfig): array
    {
        $warnings = [];

        if (empty($this->config->publicKey) || empty($this->config->secretKey)) {
            $warnings[] = 'Missing credentials! Set LANGFUSE_PUBLIC_KEY and LANGFUSE_SECRET_KEY';
        }

        if (!$otelConfig->useSimpleProcessor && $this->isRunningInConsole()) {
            $warnings[] = 'Running in CLI mode but BatchProcessor is active. ' .
                'Spans may be lost if process terminates before export. ' .
                'SimpleProcessor should auto-activate for CLI/queue jobs.';
        }

        return $warnings;
    }
}
