<?php

declare(strict_types=1);

namespace Langfuse\Tests\Unit\Observability\Spans;

use DateTime;
use Langfuse\Observability\Spans\NullSpan;
use Langfuse\Scoring\Score;
use Langfuse\Support\Contracts\IdGeneratorInterface;
use Langfuse\Support\Enums\ObservationType;
use Langfuse\Support\Enums\SpanLevel;
use Mockery;
use PHPUnit\Framework\TestCase;

class NullSpanTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_get_id_returns_null_string(): void
    {
        $span = new NullSpan();
        $this->assertEquals('null', $span->getId());
    }

    public function test_get_trace_id_returns_null_string(): void
    {
        $span = new NullSpan();
        $this->assertEquals('null', $span->getTraceId());
    }

    public function test_get_name_returns_null_string(): void
    {
        $span = new NullSpan();
        $this->assertEquals('null', $span->getName());
    }

    public function test_get_type_returns_span(): void
    {
        $span = new NullSpan();
        $this->assertEquals(ObservationType::SPAN, $span->getType());
    }

    public function test_update_returns_self(): void
    {
        $span = new NullSpan();
        
        $result = $span->update(
            name: 'new-name',
            input: ['test' => 'input'],
            output: ['test' => 'output'],
            metadata: ['key' => 'value'],
            version: '1.0.0',
            level: SpanLevel::WARNING,
            statusMessage: 'Status message',
            completionStartTime: new DateTime(),
            model: 'gpt-4',
            modelParameters: ['temperature' => 0.7],
            usageDetails: ['input' => 100, 'output' => 50],
            costDetails: ['total' => 0.01]
        );

        $this->assertSame($span, $result);
    }

    public function test_update_trace_returns_self(): void
    {
        $span = new NullSpan();
        
        $result = $span->updateTrace(
            name: 'trace-name',
            userId: 'user-123',
            sessionId: 'session-456',
            version: '1.0.0',
            input: ['test' => 'input'],
            output: ['test' => 'output'],
            metadata: ['key' => 'value'],
            tags: ['tag1', 'tag2'],
            public: true
        );

        $this->assertSame($span, $result);
    }

    public function test_score_returns_score_with_generated_id(): void
    {
        $idGenerator = Mockery::mock(IdGeneratorInterface::class);
        $idGenerator->shouldReceive('generateScoreId')
            ->once()
            ->andReturn('generated-score-id');

        $span = new NullSpan($idGenerator);
        
        $score = $span->score('accuracy', 0.95);

        $this->assertInstanceOf(Score::class, $score);
        $this->assertEquals('generated-score-id', $score->id);
        $this->assertEquals('accuracy', $score->name);
        $this->assertEquals(0.95, $score->value);
        $this->assertEquals('null', $score->traceId);
    }

    public function test_score_uses_provided_score_id(): void
    {
        $span = new NullSpan();
        
        $score = $span->score('accuracy', 0.95, 'custom-score-id');

        $this->assertEquals('custom-score-id', $score->id);
    }

    public function test_score_trace_returns_score(): void
    {
        $idGenerator = Mockery::mock(IdGeneratorInterface::class);
        $idGenerator->shouldReceive('generateScoreId')
            ->once()
            ->andReturn('trace-score-id');

        $span = new NullSpan($idGenerator);
        
        $score = $span->scoreTrace('quality', 0.8);

        $this->assertInstanceOf(Score::class, $score);
        $this->assertEquals('trace-score-id', $score->id);
        $this->assertEquals('quality', $score->name);
        $this->assertEquals(0.8, $score->value);
    }

    public function test_start_observation_returns_self(): void
    {
        $span = new NullSpan();
        
        $result = $span->startObservation(
            name: 'child-span',
            type: ObservationType::GENERATION,
            input: ['test' => 'input']
        );

        $this->assertSame($span, $result);
    }

    public function test_end_returns_self(): void
    {
        $span = new NullSpan();
        
        $result = $span->end();
        $this->assertSame($span, $result);

        $result = $span->end(new DateTime());
        $this->assertSame($span, $result);
    }

    public function test_score_generates_uuid_when_no_id_generator(): void
    {
        $span = new NullSpan(null);
        
        $score = $span->score('test', 1.0);

        $this->assertNotEmpty($score->id);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $score->id
        );
    }
}
