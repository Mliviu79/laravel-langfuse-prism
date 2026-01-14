<?php

declare(strict_types=1);

namespace Langfuse\Tests\Unit\Client;

use InvalidArgumentException;
use Langfuse\Client\Configuration;
use PHPUnit\Framework\TestCase;

class ConfigurationTest extends TestCase
{
    private array $originalEnv = [];

    protected function setUp(): void
    {
        parent::setUp();
        // Save original environment variables
        $this->saveEnvironmentVariables();
    }

    protected function tearDown(): void
    {
        // Restore original environment variables
        $this->restoreEnvironmentVariables();
        parent::tearDown();
    }

    private function saveEnvironmentVariables(): void
    {
        $vars = [
            'LANGFUSE_PUBLIC_KEY', 'LANGFUSE_SECRET_KEY', 'LANGFUSE_HOST',
            'LANGFUSE_TIMEOUT', 'LANGFUSE_DEBUG', 'LANGFUSE_TRACING_ENABLED',
            'LANGFUSE_TRACING_ENVIRONMENT', 'LANGFUSE_SAMPLE_RATE',
        ];
        foreach ($vars as $var) {
            $this->originalEnv[$var] = getenv($var);
        }
    }

    private function restoreEnvironmentVariables(): void
    {
        foreach ($this->originalEnv as $var => $value) {
            if ($value === false) {
                putenv($var);
                unset($_ENV[$var]);
            } else {
                putenv("{$var}={$value}");
                $_ENV[$var] = $value;
            }
        }
    }

    public function test_configuration_with_direct_parameters(): void
    {
        $config = new Configuration(
            publicKey: 'direct-public-key',
            secretKey: 'direct-secret-key',
            host: 'https://custom.langfuse.com',
            debug: true,
            sampleRate: 0.5
        );

        $this->assertEquals('direct-public-key', $config->publicKey);
        $this->assertEquals('direct-secret-key', $config->secretKey);
        $this->assertEquals('https://custom.langfuse.com', $config->host);
        $this->assertTrue($config->isDebugEnabled());
        $this->assertEquals(0.5, $config->sampleRate);
    }

    public function test_configuration_from_laravel_config(): void
    {
        $laravelConfig = [
            'public_key' => 'laravel-public-key',
            'secret_key' => 'laravel-secret-key',
            'host' => 'https://laravel.langfuse.com',
            'timeout' => 10,
            'debug' => true,
        ];

        $config = Configuration::fromLaravelConfig($laravelConfig);

        $this->assertEquals('laravel-public-key', $config->publicKey);
        $this->assertEquals('laravel-secret-key', $config->secretKey);
        $this->assertEquals('https://laravel.langfuse.com', $config->host);
        $this->assertEquals(10, $config->timeout);
        $this->assertTrue($config->isDebugEnabled());
    }

    public function test_api_url_generation(): void
    {
        $config = new Configuration(
            publicKey: 'test-key',
            secretKey: 'test-secret',
            host: 'https://example.com'
        );

        $this->assertEquals('https://example.com/api/public', $config->getApiUrl());
    }

    public function test_api_url_strips_trailing_slash(): void
    {
        $config = new Configuration(
            publicKey: 'test-key',
            secretKey: 'test-secret',
            host: 'https://example.com/'
        );

        $this->assertEquals('https://example.com/api/public', $config->getApiUrl());
    }

    public function test_auth_headers(): void
    {
        $config = new Configuration(
            publicKey: 'public',
            secretKey: 'secret'
        );

        $headers = $config->getAuthHeaders();

        $this->assertArrayHasKey('Authorization', $headers);
        $this->assertEquals('Basic '.base64_encode('public:secret'), $headers['Authorization']);
        $this->assertEquals('application/json', $headers['Content-Type']);
        $this->assertStringContainsString('langfuse-php/', $headers['User-Agent']);
    }

    public function test_all_headers_includes_additional_headers(): void
    {
        $config = new Configuration(
            publicKey: 'public',
            secretKey: 'secret',
            additionalHeaders: ['X-Custom-Header' => 'custom-value']
        );

        $headers = $config->getAllHeaders();

        $this->assertArrayHasKey('Authorization', $headers);
        $this->assertArrayHasKey('X-Custom-Header', $headers);
        $this->assertEquals('custom-value', $headers['X-Custom-Header']);
    }

    public function test_sampling_always_returns_false_when_rate_is_zero(): void
    {
        $config = new Configuration(
            publicKey: 'test',
            secretKey: 'test',
            sampleRate: 0.0
        );

        // Test multiple times to ensure consistency
        for ($i = 0; $i < 10; $i++) {
            $this->assertFalse($config->shouldSample());
        }
    }

    public function test_sampling_always_returns_true_when_rate_is_one(): void
    {
        $config = new Configuration(
            publicKey: 'test',
            secretKey: 'test',
            sampleRate: 1.0
        );

        // Test multiple times to ensure consistency
        for ($i = 0; $i < 10; $i++) {
            $this->assertTrue($config->shouldSample());
        }
    }

    public function test_tracing_enabled_check(): void
    {
        $enabledConfig = new Configuration(
            publicKey: 'test',
            secretKey: 'test',
            tracingEnabled: true
        );
        $this->assertTrue($enabledConfig->isTracingEnabled());

        $disabledConfig = Configuration::fromLaravelConfig([
            'public_key' => 'test',
            'secret_key' => 'test',
            'tracing_enabled' => false,
        ]);
        $this->assertFalse($disabledConfig->isTracingEnabled());
    }

    public function test_validation_skipped_when_tracing_disabled(): void
    {
        // Should not throw exception even without credentials
        $config = Configuration::fromLaravelConfig([
            'public_key' => '',
            'secret_key' => '',
            'tracing_enabled' => false,
        ]);

        $this->assertFalse($config->isTracingEnabled());
    }

    public function test_validation_throws_when_public_key_missing_and_tracing_enabled(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Public key is required');

        new Configuration(
            publicKey: '',
            secretKey: 'test-secret',
            tracingEnabled: true
        );
    }

    public function test_validation_throws_when_secret_key_missing_and_tracing_enabled(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Secret key is required');

        new Configuration(
            publicKey: 'test-public',
            secretKey: '',
            tracingEnabled: true
        );
    }

    public function test_validation_throws_for_invalid_sample_rate(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Sample rate must be between 0.0 and 1.0');

        new Configuration(
            publicKey: 'test',
            secretKey: 'test',
            sampleRate: 1.5
        );
    }

    public function test_to_array_returns_all_config_values(): void
    {
        $config = new Configuration(
            publicKey: 'test-public',
            secretKey: 'test-secret',
            host: 'https://test.com',
            timeout: 10,
            debug: true,
            tracingEnabled: true,
            environment: 'testing',
            sampleRate: 0.5
        );

        $array = $config->toArray();

        $this->assertEquals('test-public', $array['public_key']);
        $this->assertEquals('test-secret', $array['secret_key']);
        $this->assertEquals('https://test.com', $array['host']);
        $this->assertEquals(10, $array['timeout']);
        $this->assertTrue($array['debug']);
        $this->assertTrue($array['tracing_enabled']);
        $this->assertEquals('testing', $array['environment']);
        $this->assertEquals(0.5, $array['sample_rate']);
    }
}
