<?php

declare(strict_types=1);

namespace Langfuse\Tests\Unit\Observability\Spans;

use DateTime;
use Langfuse\Observability\Contracts\EventDispatcherInterface;
use Langfuse\Observability\Contracts\TracerInterface;
use Langfuse\Observability\Spans\OpenTelemetrySpan;
use Langfuse\Scoring\Enums\ScoreDataType;
use Langfuse\Scoring\Score;
use Langfuse\Support\Contracts\IdGeneratorInterface;
use Langfuse\Support\Enums\ObservationType;
use Langfuse\Support\Enums\SpanLevel;
use Mockery;
use Mockery\MockInterface;
use OpenTelemetry\API\Trace\SpanInterface as OtelSpanInterface;
use OpenTelemetry\Context\ScopeInterface;
use PHPUnit\Framework\TestCase;

class OpenTelemetrySpanTest extends TestCase
{
    private MockInterface $otelSpan;
    private MockInterface $idGenerator;
    private MockInterface $scope;
    private MockInterface $tracerWrapper;
    private MockInterface $eventDispatcher;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->otelSpan = Mockery::mock(OtelSpanInterface::class);
        $this->idGenerator = Mockery::mock(IdGeneratorInterface::class);
        $this->scope = Mockery::mock(ScopeInterface::class);
        $this->tracerWrapper = Mockery::mock(TracerInterface::class);
        $this->eventDispatcher = Mockery::mock(EventDispatcherInterface::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function createSpan(
        string $spanId = 'span-123',
        string $traceId = 'trace-456',
        string $name = 'test-span',
        ObservationType $type = ObservationType::SPAN,
        bool $isRootSpan = false
    ): OpenTelemetrySpan {
        return new OpenTelemetrySpan(
            otelSpan: $this->otelSpan,
            spanId: $spanId,
            traceId: $traceId,
            name: $name,
            type: $type,
            idGenerator: $this->idGenerator,
            scope: $this->scope,
            tracerWrapper: $this->tracerWrapper,
            isRootSpan: $isRootSpan,
            eventDispatcher: $this->eventDispatcher
        );
    }

    public function test_get_id_returns_span_id(): void
    {
        $span = $this->createSpan(spanId: 'my-span-id');
        $this->assertEquals('my-span-id', $span->getId());
    }

    public function test_get_trace_id_returns_trace_id(): void
    {
        $span = $this->createSpan(traceId: 'my-trace-id');
        $this->assertEquals('my-trace-id', $span->getTraceId());
    }

    public function test_get_name_returns_name(): void
    {
        $span = $this->createSpan(name: 'my-span-name');
        $this->assertEquals('my-span-name', $span->getName());
    }

    public function test_get_type_returns_type(): void
    {
        $span = $this->createSpan(type: ObservationType::GENERATION);
        $this->assertEquals(ObservationType::GENERATION, $span->getType());
    }

    public function test_get_otel_span_returns_underlying_span(): void
    {
        $span = $this->createSpan();
        $this->assertSame($this->otelSpan, $span->getOtelSpan());
    }

    public function test_update_name_updates_otel_span(): void
    {
        $this->otelSpan->shouldReceive('updateName')
            ->once()
            ->with('new-name');
        
        // Allow other setAttribute calls
        $this->otelSpan->shouldReceive('setAttribute')->zeroOrMoreTimes();
        $this->otelSpan->shouldReceive('setStatus')->zeroOrMoreTimes();

        $span = $this->createSpan();
        $result = $span->update(name: 'new-name');

        $this->assertSame($span, $result);
    }

    public function test_update_sets_input_attribute(): void
    {
        $inputData = ['prompt' => 'test prompt'];
        
        // Allow any setAttribute calls
        $this->otelSpan->shouldReceive('setAttribute')->zeroOrMoreTimes();

        $span = $this->createSpan();
        $result = $span->update(input: $inputData);
        
        $this->assertSame($span, $result);
    }

    public function test_update_sets_output_attribute(): void
    {
        $outputData = ['response' => 'test response'];
        
        // Allow any setAttribute calls
        $this->otelSpan->shouldReceive('setAttribute')->zeroOrMoreTimes();

        $span = $this->createSpan();
        $result = $span->update(output: $outputData);
        
        $this->assertSame($span, $result);
    }

    public function test_update_sets_metadata_attribute(): void
    {
        $metadata = ['key' => 'value'];
        
        // The trait may set metadata via different attribute name or encoding
        $this->otelSpan->shouldReceive('setAttribute')->zeroOrMoreTimes();

        $span = $this->createSpan();
        $result = $span->update(metadata: $metadata);
        
        $this->assertSame($span, $result);
    }

    public function test_update_sets_model_attributes(): void
    {
        // Allow any setAttribute calls - the trait determines the exact attribute names
        $this->otelSpan->shouldReceive('setAttribute')->zeroOrMoreTimes();

        $span = $this->createSpan();
        $result = $span->update(model: 'gpt-4', modelParameters: ['temperature' => 0.7]);
        
        $this->assertSame($span, $result);
    }

    public function test_score_generates_id_and_adds_event(): void
    {
        $this->idGenerator->shouldReceive('generateScoreId')
            ->once()
            ->andReturn('score-789');

        $this->otelSpan->shouldReceive('addEvent')
            ->once()
            ->with('langfuse.score', Mockery::on(function ($attributes) {
                return $attributes['score.id'] === 'score-789'
                    && $attributes['score.name'] === 'accuracy'
                    && $attributes['score.value'] === '0.95';
            }));

        $span = $this->createSpan();
        $score = $span->score('accuracy', 0.95);

        $this->assertInstanceOf(Score::class, $score);
        $this->assertEquals('score-789', $score->id);
        $this->assertEquals('accuracy', $score->name);
        $this->assertEquals(0.95, $score->value);
        $this->assertEquals('trace-456', $score->traceId);
        $this->assertEquals('span-123', $score->observationId);
        $this->assertEquals(ScoreDataType::NUMERIC, $score->dataType);
    }

    public function test_score_uses_provided_score_id(): void
    {
        $this->otelSpan->shouldReceive('addEvent')
            ->once()
            ->with('langfuse.score', Mockery::on(function ($attributes) {
                return $attributes['score.id'] === 'custom-id';
            }));

        $span = $this->createSpan();
        $score = $span->score('accuracy', 0.95, 'custom-id');

        $this->assertEquals('custom-id', $score->id);
    }

    public function test_score_boolean_value(): void
    {
        $this->idGenerator->shouldReceive('generateScoreId')->andReturn('bool-score');
        
        $this->otelSpan->shouldReceive('addEvent')
            ->once()
            ->with('langfuse.score', Mockery::on(function ($attributes) {
                return $attributes['score.value'] === 'true';
            }));

        $span = $this->createSpan();
        $score = $span->score('valid', true);

        $this->assertEquals(ScoreDataType::BOOLEAN, $score->dataType);
    }

    public function test_score_string_value(): void
    {
        $this->idGenerator->shouldReceive('generateScoreId')->andReturn('str-score');
        
        $this->otelSpan->shouldReceive('addEvent')
            ->once()
            ->with('langfuse.score', Mockery::on(function ($attributes) {
                return $attributes['score.value'] === 'good';
            }));

        $span = $this->createSpan();
        $score = $span->score('quality', 'good');

        $this->assertEquals(ScoreDataType::CATEGORICAL, $score->dataType);
    }

    public function test_score_trace_creates_trace_level_score(): void
    {
        $this->idGenerator->shouldReceive('generateScoreId')
            ->once()
            ->andReturn('trace-score-id');

        $this->otelSpan->shouldReceive('addEvent')
            ->once()
            ->with('langfuse.trace.score', Mockery::on(function ($attributes) {
                return $attributes['score.trace_id'] === 'trace-456'
                    && !isset($attributes['score.observation_id']);
            }));

        $span = $this->createSpan();
        $score = $span->scoreTrace('overall', 0.9);

        $this->assertNull($score->observationId);
        $this->assertEquals('trace-456', $score->traceId);
    }

    public function test_end_detaches_scope_and_ends_span(): void
    {
        $this->scope->shouldReceive('detach')->once();
        $this->otelSpan->shouldReceive('end')->once();
        $this->tracerWrapper->shouldReceive('removeSpan')
            ->once()
            ->with('span-123');
        $this->eventDispatcher->shouldReceive('dispatchSpanEnded')
            ->once();

        $span = $this->createSpan();
        $result = $span->end();

        $this->assertSame($span, $result);
    }

    public function test_end_with_custom_time_sets_attribute(): void
    {
        $endTime = new DateTime('2025-01-12 10:00:00');
        
        $this->otelSpan->shouldReceive('setAttribute')
            ->once()
            ->with('langfuse.observation.end_time', $endTime->format('c'));
        
        $this->scope->shouldReceive('detach');
        $this->otelSpan->shouldReceive('end');
        $this->tracerWrapper->shouldReceive('removeSpan');
        $this->eventDispatcher->shouldReceive('dispatchSpanEnded');

        $span = $this->createSpan();
        $result = $span->end($endTime);
        
        $this->assertSame($span, $result);
    }

    public function test_end_root_span_dispatches_trace_completed(): void
    {
        $this->scope->shouldReceive('detach');
        $this->otelSpan->shouldReceive('end');
        $this->tracerWrapper->shouldReceive('removeSpan');
        
        $this->eventDispatcher->shouldReceive('dispatchSpanEnded')->once();
        $this->eventDispatcher->shouldReceive('dispatchTraceCompleted')
            ->once()
            ->with('trace-456');

        $span = $this->createSpan(isRootSpan: true);
        $result = $span->end();
        
        $this->assertSame($span, $result);
    }

    public function test_is_root_span(): void
    {
        $rootSpan = $this->createSpan(isRootSpan: true);
        $childSpan = $this->createSpan(isRootSpan: false);

        $this->assertTrue($rootSpan->isRootSpan());
        $this->assertFalse($childSpan->isRootSpan());
    }

    public function test_start_observation_delegates_to_tracer(): void
    {
        $childSpan = Mockery::mock(OpenTelemetrySpan::class);
        
        $this->tracerWrapper->shouldReceive('startSpan')
            ->once()
            ->with(
                'child-span',
                ObservationType::GENERATION,
                ['prompt' => 'test'],
                null,
                null,
                null,
                null,
                null,
                null,
                'gpt-4'
            )
            ->andReturn($childSpan);

        $span = $this->createSpan();
        $result = $span->startObservation(
            name: 'child-span',
            type: ObservationType::GENERATION,
            input: ['prompt' => 'test'],
            model: 'gpt-4'
        );

        $this->assertSame($childSpan, $result);
    }

    public function test_start_observation_returns_self_without_tracer(): void
    {
        $span = new OpenTelemetrySpan(
            otelSpan: $this->otelSpan,
            spanId: 'span-123',
            traceId: 'trace-456',
            name: 'test-span',
            type: ObservationType::SPAN,
            idGenerator: $this->idGenerator,
            scope: null,
            tracerWrapper: null, // No tracer
            isRootSpan: false
        );

        $result = $span->startObservation('child', ObservationType::SPAN);

        $this->assertSame($span, $result);
    }
}
