<?php

declare(strict_types=1);

namespace Langfuse\Tests\Unit\Client\Services;

use Langfuse\Client\Configuration;
use Langfuse\Client\Services\TracingService;
use Langfuse\Observability\Contracts\SpanInterface;
use Langfuse\Observability\Contracts\TracerInterface;
use Langfuse\Observability\Spans\NullSpan;
use Langfuse\Support\Contracts\IdGeneratorInterface;
use Langfuse\Support\Enums\ObservationType;
use Langfuse\Support\Enums\SpanLevel;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class TracingServiceTest extends TestCase
{
    private MockInterface $tracer;
    private MockInterface $idGenerator;
    private Configuration $enabledConfig;
    private Configuration $disabledConfig;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->tracer = Mockery::mock(TracerInterface::class);
        $this->idGenerator = Mockery::mock(IdGeneratorInterface::class);
        
        $this->enabledConfig = new Configuration(
            publicKey: 'test-key',
            secretKey: 'test-secret',
            tracingEnabled: true,
            sampleRate: 1.0
        );
        
        $this->disabledConfig = Configuration::fromLaravelConfig([
            'public_key' => '',
            'secret_key' => '',
            'tracing_enabled' => false,
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_start_span_returns_null_span_when_tracing_disabled(): void
    {
        $service = new TracingService(
            $this->disabledConfig,
            $this->tracer,
            $this->idGenerator
        );

        $span = $service->startSpan('test-span');

        $this->assertInstanceOf(NullSpan::class, $span);
    }

    public function test_start_span_returns_null_span_when_sampling_rejects(): void
    {
        $noSampleConfig = new Configuration(
            publicKey: 'test-key',
            secretKey: 'test-secret',
            tracingEnabled: true,
            sampleRate: 0.0 // Always reject
        );

        $service = new TracingService(
            $noSampleConfig,
            $this->tracer,
            $this->idGenerator
        );

        $span = $service->startSpan('test-span');

        $this->assertInstanceOf(NullSpan::class, $span);
    }

    public function test_start_span_delegates_to_tracer_when_enabled(): void
    {
        $expectedSpan = Mockery::mock(SpanInterface::class);
        
        $this->tracer->shouldReceive('startSpan')
            ->once()
            ->with(
                'test-span',
                ObservationType::SPAN,
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                null
            )
            ->andReturn($expectedSpan);

        $service = new TracingService(
            $this->enabledConfig,
            $this->tracer,
            $this->idGenerator
        );

        $span = $service->startSpan('test-span');

        $this->assertSame($expectedSpan, $span);
    }

    public function test_start_span_passes_all_parameters_to_tracer(): void
    {
        $expectedSpan = Mockery::mock(SpanInterface::class);
        $metadata = ['key' => 'value'];
        $input = ['prompt' => 'test'];
        $output = ['response' => 'test'];
        
        $this->tracer->shouldReceive('startSpan')
            ->once()
            ->withArgs(function (
                $name,
                $type,
                $inputArg,
                $outputArg,
                $meta,
                $version,
                $level,
                $statusMessage,
                $parentId,
                $model
            ) use ($metadata, $input, $output) {
                return $name === 'test-span'
                    && $type === ObservationType::GENERATION
                    && $inputArg === $input
                    && $outputArg === $output
                    && $meta === $metadata
                    && $version === '1.0.0'
                    && $level === SpanLevel::WARNING
                    && $statusMessage === 'Status'
                    && $parentId === 'parent-123'
                    && $model === 'gpt-4';
            })
            ->andReturn($expectedSpan);

        $service = new TracingService(
            $this->enabledConfig,
            $this->tracer,
            $this->idGenerator
        );

        $span = $service->startSpan(
            name: 'test-span',
            type: ObservationType::GENERATION,
            input: $input,
            output: $output,
            metadata: $metadata,
            version: '1.0.0',
            level: SpanLevel::WARNING,
            statusMessage: 'Status',
            parentId: 'parent-123',
            model: 'gpt-4'
        );

        $this->assertSame($expectedSpan, $span);
    }

    public function test_flush_delegates_to_tracer(): void
    {
        $this->tracer->shouldReceive('flush')->once();

        $service = new TracingService(
            $this->enabledConfig,
            $this->tracer,
            $this->idGenerator
        );

        $service->flush();
        
        $this->assertTrue(true); // Verify no exception
    }

    public function test_shutdown_delegates_to_tracer(): void
    {
        $this->tracer->shouldReceive('shutdown')->once();

        $service = new TracingService(
            $this->enabledConfig,
            $this->tracer,
            $this->idGenerator
        );

        $service->shutdown();
        
        $this->assertTrue(true); // Verify no exception
    }
}
