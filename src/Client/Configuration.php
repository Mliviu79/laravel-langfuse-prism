<?php

declare(strict_types=1);

namespace Langfuse\Client;

use InvalidArgumentException;

final readonly class Configuration
{
    public string $publicKey;

    public string $secretKey;

    public string $host;

    public int $timeout;

    public bool $debug;

    public bool $tracingEnabled;

    public string $environment;

    public ?string $release;

    public int $mediaUploadThreadCount;

    public float $sampleRate;

    public array $additionalHeaders;

    public array $blockedInstrumentationScopes;

    public ?string $otelEndpoint;

    public string $otelProtocol;

    public bool $otelUseCollector;

    public array $otelHeaders;

    public function __construct(
        ?string $publicKey = null,
        ?string $secretKey = null,
        ?string $host = null,
        ?int $timeout = null,
        ?bool $debug = null,
        ?bool $tracingEnabled = null,
        ?string $environment = null,
        ?string $release = null,
        ?int $mediaUploadThreadCount = null,
        ?float $sampleRate = null,
        ?array $additionalHeaders = null,
        ?array $blockedInstrumentationScopes = null,
        ?string $otelEndpoint = null,
        ?string $otelProtocol = null,
        ?bool $otelUseCollector = null,
        ?array $otelHeaders = null,
    ) {
        // IMPORTANT: Determine tracingEnabled FIRST before any validation
        // This allows the config to be created even without API keys when tracing is disabled
        $this->tracingEnabled = $tracingEnabled ?? $this->getBoolEnvValue('LANGFUSE_TRACING_ENABLED', true);
        
        // Assign all other values
        $this->publicKey = $publicKey ?? $this->getEnvValue('LANGFUSE_PUBLIC_KEY', '');
        $this->secretKey = $secretKey ?? $this->getEnvValue('LANGFUSE_SECRET_KEY', '');
        $this->host = $host ?? $this->getEnvValue('LANGFUSE_HOST', 'https://cloud.langfuse.com');
        $this->timeout = $timeout ?? (int) $this->getEnvValue('LANGFUSE_TIMEOUT', '5');
        $this->debug = $debug ?? $this->getBoolEnvValue('LANGFUSE_DEBUG', false);
        $this->environment = $environment ?? $this->getEnvValue('LANGFUSE_TRACING_ENVIRONMENT', 'production');
        $this->release = $release ?? $this->getEnvValue('LANGFUSE_RELEASE');
        $this->mediaUploadThreadCount = $mediaUploadThreadCount ?? (int) $this->getEnvValue('LANGFUSE_MEDIA_UPLOAD_THREAD_COUNT', '1');
        $this->sampleRate = $sampleRate ?? (float) $this->getEnvValue('LANGFUSE_SAMPLE_RATE', '1.0');
        $this->additionalHeaders = $additionalHeaders ?? [];
        $this->blockedInstrumentationScopes = $blockedInstrumentationScopes ?? [];
        $this->otelEndpoint = $otelEndpoint ?? $this->getEnvValue('LANGFUSE_OTEL_ENDPOINT', $this->host.'/api/public/otel');
        $this->otelProtocol = $otelProtocol ?? $this->getEnvValue('LANGFUSE_OTEL_PROTOCOL', 
            $this->getEnvValue('OTEL_EXPORTER_OTLP_PROTOCOL', 'http/json'));
        
        // Auto-detect if using a collector (endpoint doesn't point to Langfuse host)
        $this->otelUseCollector = $otelUseCollector ?? $this->getBoolEnvValue('LANGFUSE_OTEL_USE_COLLECTOR', 
            !str_contains($this->otelEndpoint, $this->host));

        // Parse OTEL headers from environment variable or use provided array
        $this->otelHeaders = $otelHeaders ?? $this->parseOtelHeaders();

        // Only validate if tracing is enabled - this allows the SDK to be installed
        // without API keys when tracing is disabled (e.g., local development)
        if ($this->tracingEnabled) {
            $this->validate();
        } else {
            // Still validate non-credential settings
            $this->validateNonCredentials();
        }
    }

    /**
     * Create configuration from Laravel config array
     */
    public static function fromLaravelConfig(array $config): self
    {
        return new self(
            publicKey: $config['public_key'] ?? null,
            secretKey: $config['secret_key'] ?? null,
            host: $config['host'] ?? null,
            timeout: $config['timeout'] ?? null,
            debug: $config['debug'] ?? null,
            tracingEnabled: $config['tracing_enabled'] ?? null,
            environment: $config['environment'] ?? null,
            release: $config['release'] ?? null,
            mediaUploadThreadCount: $config['media_upload_thread_count'] ?? null,
            sampleRate: $config['sample_rate'] ?? null,
            additionalHeaders: $config['additional_headers'] ?? null,
            blockedInstrumentationScopes: $config['blocked_instrumentation_scopes'] ?? null,
            otelEndpoint: $config['otel_endpoint'] ?? null,
            otelProtocol: $config['otel_protocol'] ?? null,
            otelUseCollector: $config['otel_use_collector'] ?? null,
            otelHeaders: $config['otel_headers'] ?? null,
        );
    }

    /**
     * Get the base URL for API requests
     */
    public function getApiUrl(): string
    {
        return rtrim($this->host, '/').'/api/public';
    }

    /**
     * Check if debug mode is enabled
     */
    public function isDebugEnabled(): bool
    {
        return $this->debug;
    }

    /**
     * Check if tracing is enabled
     */
    public function isTracingEnabled(): bool
    {
        return $this->tracingEnabled;
    }

    /**
     * Check if sampling allows this trace
     */
    public function shouldSample(): bool
    {
        if ($this->sampleRate >= 1.0) {
            return true;
        }

        if ($this->sampleRate <= 0.0) {
            return false;
        }

        return mt_rand() / mt_getrandmax() <= $this->sampleRate;
    }

    /**
     * Get authentication headers for API requests
     */
    public function getAuthHeaders(): array
    {
        return [
            'Authorization' => 'Basic '.base64_encode($this->publicKey.':'.$this->secretKey),
            'Content-Type' => 'application/json',
            'User-Agent' => $this->getUserAgent(),
        ];
    }

    /**
     * Get all headers including additional ones
     */
    public function getAllHeaders(): array
    {
        return array_merge($this->getAuthHeaders(), $this->additionalHeaders);
    }

    /**
     * Get the user agent string
     */
    public function getUserAgent(): string
    {
        $phpVersion = PHP_VERSION;
        $sdkVersion = $this->getSdkVersion();

        return "langfuse-php/{$sdkVersion} php/{$phpVersion}";
    }

    /**
     * Get the SDK version
     */
    private function getSdkVersion(): string
    {
        // This would typically be replaced during build process
        return '1.0.0-dev';
    }

    /**
     * Validate all configuration values (including credentials)
     * Only called when tracing is enabled
     */
    private function validate(): void
    {
        // Validate credentials
        if (empty($this->publicKey)) {
            throw new InvalidArgumentException('Public key is required. Set LANGFUSE_PUBLIC_KEY environment variable or pass publicKey parameter.');
        }

        if (empty($this->secretKey)) {
            throw new InvalidArgumentException('Secret key is required. Set LANGFUSE_SECRET_KEY environment variable or pass secretKey parameter.');
        }

        // Validate non-credential settings
        $this->validateNonCredentials();
    }

    /**
     * Validate non-credential configuration values
     * Called even when tracing is disabled
     */
    private function validateNonCredentials(): void
    {
        if (empty($this->host)) {
            throw new InvalidArgumentException('Host is required. Set LANGFUSE_HOST environment variable or pass host parameter.');
        }

        if (! filter_var($this->host, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException("Invalid host URL: {$this->host}");
        }

        if ($this->timeout <= 0) {
            throw new InvalidArgumentException('Timeout must be a positive integer.');
        }

        if ($this->sampleRate < 0.0 || $this->sampleRate > 1.0) {
            throw new InvalidArgumentException('Sample rate must be between 0.0 and 1.0.');
        }

        if ($this->mediaUploadThreadCount <= 0) {
            throw new InvalidArgumentException('Media upload thread count must be a positive integer.');
        }

        if (! $this->isValidEnvironmentName($this->environment)) {
            throw new InvalidArgumentException(
                'Environment must be a lowercase alphanumeric string with hyphens and underscores, and cannot start with "langfuse".'
            );
        }
    }

    /**
     * Check if environment name is valid
     */
    private function isValidEnvironmentName(string $environment): bool
    {
        // Must be lowercase alphanumeric with hyphens and underscores
        if (! preg_match('/^[a-z0-9_-]+$/', $environment)) {
            return false;
        }

        // Cannot start with 'langfuse'
        if (str_starts_with($environment, 'langfuse')) {
            return false;
        }

        return true;
    }

    /**
     * Get environment variable value with default
     */
    private function getEnvValue(string $key, ?string $default = null): ?string
    {
        $value = $_ENV[$key] ?? getenv($key) ?: $default;

        return $value === '' ? null : $value;
    }

    /**
     * Get boolean environment variable value
     */
    private function getBoolEnvValue(string $key, bool $default): bool
    {
        $value = $this->getEnvValue($key);

        if ($value === null) {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Parse OTEL headers from environment variable
     * Expects format: key1=value1,key2=value2
     */
    private function parseOtelHeaders(): array
    {
        $headersEnv = $this->getEnvValue('OTEL_EXPORTER_OTLP_HEADERS');
        if ($headersEnv === null || $headersEnv === '') {
            return [];
        }

        $headers = [];
        $pairs = explode(',', $headersEnv);

        foreach ($pairs as $pair) {
            $parts = explode('=', trim($pair), 2);
            if (count($parts) === 2) {
                $headers[trim($parts[0])] = trim($parts[1]);
            }
        }

        return $headers;
    }

    /**
     * Convert configuration to array
     */
    public function toArray(): array
    {
        return [
            'public_key' => $this->publicKey,
            'secret_key' => $this->secretKey,
            'host' => $this->host,
            'timeout' => $this->timeout,
            'debug' => $this->debug,
            'tracing_enabled' => $this->tracingEnabled,
            'environment' => $this->environment,
            'release' => $this->release,
            'media_upload_thread_count' => $this->mediaUploadThreadCount,
            'sample_rate' => $this->sampleRate,
            'additional_headers' => $this->additionalHeaders,
            'blocked_instrumentation_scopes' => $this->blockedInstrumentationScopes,
            'otel_endpoint' => $this->otelEndpoint,
            'otel_protocol' => $this->otelProtocol,
            'otel_use_collector' => $this->otelUseCollector,
            'otel_headers' => $this->otelHeaders,
        ];
    }
}
