<?php

declare(strict_types=1);

namespace Langfuse\Tests;

use Langfuse\Client\Configuration;
use Langfuse\Integration\Laravel\LangfuseServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

/**
 * Base test case for Laravel Orchestra Testbench integration tests.
 */
abstract class TestCase extends OrchestraTestCase
{
    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        $providers = [
            LangfuseServiceProvider::class,
        ];

        if (class_exists(\Keepsuit\LaravelOpenTelemetry\LaravelOpenTelemetryServiceProvider::class)) {
            $providers[] = \Keepsuit\LaravelOpenTelemetry\LaravelOpenTelemetryServiceProvider::class;
        }

        return $providers;
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function defineEnvironment($app): void
    {
        // Set up default test configuration using real credentials from phpunit.xml
        $app['config']->set('langfuse', [
            'public_key' => env('LANGFUSE_PUBLIC_KEY'),
            'secret_key' => env('LANGFUSE_SECRET_KEY'),
            'host' => env('LANGFUSE_HOST', 'https://cloud.langfuse.com'),
            'timeout' => 30,
            'debug' => filter_var(env('LANGFUSE_DEBUG', true), FILTER_VALIDATE_BOOLEAN),
            'tracing_enabled' => filter_var(env('LANGFUSE_TRACING_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
            'environment' => env('LANGFUSE_TRACING_ENVIRONMENT', 'testing'),
            'sample_rate' => (float) env('LANGFUSE_SAMPLE_RATE', 1.0),
            'otel_enabled' => filter_var(env('LANGFUSE_OTEL_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
            'otel_endpoint' => env('LANGFUSE_OTEL_ENDPOINT', 'https://cloud.langfuse.com/api/public/otel'),
            'otel_protocol' => env('LANGFUSE_OTEL_PROTOCOL', 'http/protobuf'),
            'prism' => [
                'auto_trace' => true,
                'trace_model_params' => true,
                'trace_usage' => true,
                'trace_cost' => true,
            ],
            'integrations' => [
                'keepsuit_enabled' => false,
            ],
        ]);
    }

    /**
     * Create a test configuration with tracing enabled.
     */
    protected function createEnabledConfiguration(array $overrides = []): Configuration
    {
        return Configuration::fromLaravelConfig(array_merge([
            'public_key' => 'test-public-key',
            'secret_key' => 'test-secret-key',
            'host' => 'https://test.langfuse.com',
            'timeout' => 5,
            'debug' => false,
            'tracing_enabled' => true,
            'environment' => 'testing',
            'sample_rate' => 1.0,
        ], $overrides));
    }

    /**
     * Create a test configuration with tracing disabled.
     */
    protected function createDisabledConfiguration(array $overrides = []): Configuration
    {
        return Configuration::fromLaravelConfig(array_merge([
            'public_key' => '',
            'secret_key' => '',
            'host' => 'https://test.langfuse.com',
            'timeout' => 5,
            'debug' => false,
            'tracing_enabled' => false,
            'environment' => 'testing',
            'sample_rate' => 1.0,
        ], $overrides));
    }

    /**
     * Create a real configuration for integration tests.
     * Uses actual environment variables.
     */
    protected function createIntegrationConfiguration(): Configuration
    {
        return Configuration::fromLaravelConfig([
            'public_key' => env('LANGFUSE_PUBLIC_KEY'),
            'secret_key' => env('LANGFUSE_SECRET_KEY'),
            'host' => env('LANGFUSE_HOST', 'https://cloud.langfuse.com'),
            'timeout' => 30,
            'debug' => true,
            'tracing_enabled' => true,
            'environment' => 'testing',
            'sample_rate' => 1.0,
            'otel_endpoint' => env('LANGFUSE_OTEL_ENDPOINT', 'https://cloud.langfuse.com/api/public/otel'),
            'otel_protocol' => 'http/protobuf',
        ]);
    }
}
