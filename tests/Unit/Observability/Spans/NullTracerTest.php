<?php

declare(strict_types=1);

namespace Langfuse\Tests\Unit\Observability\Spans;

use Langfuse\Observability\Spans\NullSpan;
use Langfuse\Observability\Spans\NullTracer;
use Langfuse\Support\Contracts\IdGeneratorInterface;
use Langfuse\Support\Enums\ObservationType;
use Langfuse\Support\Enums\SpanLevel;
use Mockery;
use PHPUnit\Framework\TestCase;

class NullTracerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_start_span_returns_null_span(): void
    {
        $tracer = new NullTracer();
        
        $span = $tracer->startSpan('test-span');

        $this->assertInstanceOf(NullSpan::class, $span);
    }

    public function test_start_span_with_all_parameters_returns_null_span(): void
    {
        $tracer = new NullTracer();
        
        $span = $tracer->startSpan(
            name: 'test-span',
            type: ObservationType::GENERATION,
            input: ['prompt' => 'test'],
            output: ['response' => 'test'],
            metadata: ['key' => 'value'],
            version: '1.0.0',
            level: SpanLevel::DEFAULT,
            statusMessage: 'Status',
            parentId: 'parent-123',
            model: 'gpt-4'
        );

        $this->assertInstanceOf(NullSpan::class, $span);
    }

    public function test_get_span_returns_null(): void
    {
        $tracer = new NullTracer();
        
        $result = $tracer->getSpan('any-span-id');

        $this->assertNull($result);
    }

    public function test_remove_span_does_nothing(): void
    {
        $tracer = new NullTracer();
        
        // Should not throw any exception
        $tracer->removeSpan('any-span-id');

        $this->assertTrue(true);
    }

    public function test_flush_does_nothing(): void
    {
        $tracer = new NullTracer();
        
        // Should not throw any exception
        $tracer->flush();

        $this->assertTrue(true);
    }

    public function test_shutdown_does_nothing(): void
    {
        $tracer = new NullTracer();
        
        // Should not throw any exception
        $tracer->shutdown();

        $this->assertTrue(true);
    }

    public function test_tracer_passes_id_generator_to_null_span(): void
    {
        $idGenerator = Mockery::mock(IdGeneratorInterface::class);
        $idGenerator->shouldReceive('generateScoreId')
            ->once()
            ->andReturn('generated-id');

        $tracer = new NullTracer($idGenerator);
        $span = $tracer->startSpan('test');
        
        // The span should have the id generator
        $score = $span->score('test', 1.0);
        $this->assertEquals('generated-id', $score->id);
    }
}
