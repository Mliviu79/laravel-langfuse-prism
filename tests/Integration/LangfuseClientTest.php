<?php

declare(strict_types=1);

namespace Langfuse\Tests\Integration;

use Langfuse\Api\Contracts\ApiClientInterface;
use Langfuse\Client\Configuration;
use Langfuse\Client\LangfuseClient;
use Langfuse\Client\Services\DatasetService;
use Langfuse\Client\Services\ScoreService;
use Langfuse\Client\Services\TracingService;
use Langfuse\Observability\Spans\NullSpan;
use Mockery;
use PHPUnit\Framework\TestCase;

class LangfuseClientTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_start_span_delegates_to_tracing_service(): void
    {
        $config = new Configuration(
            publicKey: 'test-key',
            secretKey: 'test-secret',
            tracingEnabled: true,
            sampleRate: 1.0
        );

        $apiClient = Mockery::mock(ApiClientInterface::class);
        $span = new NullSpan;

        $tracingService = Mockery::mock(TracingService::class);
        $tracingService->shouldReceive('startSpan')
            ->once()
            ->andReturn($span);

        $client = new LangfuseClient(
            config: $config,
            apiClient: $apiClient,
            tracingService: $tracingService,
            datasetService: Mockery::mock(DatasetService::class),
            scoreService: Mockery::mock(ScoreService::class),
        );

        $result = $client->startSpan('test-span');

        $this->assertSame($span, $result);
    }

    public function test_flush_delegates_to_tracing_service(): void
    {
        $config = new Configuration(
            publicKey: 'test-key',
            secretKey: 'test-secret'
        );
        $apiClient = Mockery::mock(ApiClientInterface::class);
        $tracingService = Mockery::mock(TracingService::class);
        $tracingService->shouldReceive('flush')->once()->andReturnNull();

        $client = new LangfuseClient(
            config: $config,
            apiClient: $apiClient,
            tracingService: $tracingService,
            datasetService: Mockery::mock(DatasetService::class),
            scoreService: Mockery::mock(ScoreService::class),
        );

        $client->flush();
        $this->assertTrue(true); // Verify flush was called
    }

    public function test_shutdown_delegates_to_tracing_service(): void
    {
        $config = new Configuration(
            publicKey: 'test-key',
            secretKey: 'test-secret'
        );
        $apiClient = Mockery::mock(ApiClientInterface::class);
        $tracingService = Mockery::mock(TracingService::class);
        $tracingService->shouldReceive('shutdown')->once()->andReturnNull();

        $client = new LangfuseClient(
            config: $config,
            apiClient: $apiClient,
            tracingService: $tracingService,
            datasetService: Mockery::mock(DatasetService::class),
            scoreService: Mockery::mock(ScoreService::class),
        );

        $client->shutdown();
        $this->assertTrue(true); // Verify shutdown was called
    }
}
