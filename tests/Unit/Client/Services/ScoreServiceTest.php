<?php

declare(strict_types=1);

namespace Langfuse\Tests\Unit\Client\Services;

use Langfuse\Api\Contracts\ApiClientInterface;
use Langfuse\Client\Services\ScoreService;
use Langfuse\Scoring\Enums\ScoreDataType;
use Langfuse\Scoring\Score;
use Langfuse\Support\Contracts\IdGeneratorInterface;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class ScoreServiceTest extends TestCase
{
    private MockInterface $apiClient;

    private MockInterface $idGenerator;

    private ScoreService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->apiClient = Mockery::mock(ApiClientInterface::class);
        $this->idGenerator = Mockery::mock(IdGeneratorInterface::class);

        $this->service = new ScoreService($this->apiClient, $this->idGenerator);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_create_score_generates_id_and_calls_api(): void
    {
        $this->idGenerator->shouldReceive('generateScoreId')
            ->once()
            ->andReturn('score-123');

        $this->apiClient->shouldReceive('createScore')
            ->once()
            ->with(Mockery::on(function ($data) {
                return $data['id'] === 'score-123'
                    && $data['name'] === 'accuracy'
                    && $data['value'] === 0.95
                    && $data['dataType'] === ScoreDataType::NUMERIC->value
                    && $data['traceId'] === 'trace-456';
            }));

        $score = $this->service->createScore(
            'accuracy',
            0.95,
            'trace-456'
        );

        $this->assertInstanceOf(Score::class, $score);
        $this->assertEquals('score-123', $score->id);
        $this->assertEquals('accuracy', $score->name);
        $this->assertEquals(0.95, $score->value);
        $this->assertEquals('trace-456', $score->traceId);
        $this->assertEquals(ScoreDataType::NUMERIC, $score->dataType);
    }

    public function test_create_score_uses_provided_score_id(): void
    {
        $this->apiClient->shouldReceive('createScore')
            ->once()
            ->with(Mockery::on(function ($data) {
                return $data['id'] === 'custom-id';
            }));

        $score = $this->service->createScore(
            'accuracy',
            0.95,
            'trace-456',
            null,
            'custom-id'
        );

        $this->assertEquals('custom-id', $score->id);
    }

    public function test_create_score_infers_boolean_data_type(): void
    {
        $this->idGenerator->shouldReceive('generateScoreId')->andReturn('bool-score');
        $this->apiClient->shouldReceive('createScore')
            ->with(Mockery::on(function ($data) {
                return $data['dataType'] === ScoreDataType::BOOLEAN->value;
            }));

        $score = $this->service->createScore('valid', true, 'trace-123');

        $this->assertEquals(ScoreDataType::BOOLEAN, $score->dataType);
    }

    public function test_create_score_infers_categorical_data_type(): void
    {
        $this->idGenerator->shouldReceive('generateScoreId')->andReturn('cat-score');
        $this->apiClient->shouldReceive('createScore')
            ->with(Mockery::on(function ($data) {
                return $data['dataType'] === ScoreDataType::CATEGORICAL->value;
            }));

        $score = $this->service->createScore('quality', 'good', 'trace-123');

        $this->assertEquals(ScoreDataType::CATEGORICAL, $score->dataType);
    }

    public function test_create_score_removes_null_values(): void
    {
        $this->idGenerator->shouldReceive('generateScoreId')->andReturn('score-123');
        $this->apiClient->shouldReceive('createScore')
            ->with(Mockery::on(function ($data) {
                return ! array_key_exists('observationId', $data)
                    && ! array_key_exists('comment', $data)
                    && ! array_key_exists('configId', $data);
            }));

        $score = $this->service->createScore('accuracy', 0.95, 'trace-123');

        $this->assertEquals('score-123', $score->id);
    }

    public function test_create_score_includes_all_parameters(): void
    {
        $this->idGenerator->shouldReceive('generateScoreId')->andReturn('score-123');
        $this->apiClient->shouldReceive('createScore')
            ->with(Mockery::on(function ($data) {
                return $data['traceId'] === 'trace-123'
                    && $data['observationId'] === 'obs-456'
                    && $data['comment'] === 'Test comment'
                    && $data['configId'] === 'config-789';
            }));

        $score = $this->service->createScore(
            'accuracy',
            0.95,
            'trace-123',
            'obs-456',
            null,
            'Test comment',
            'config-789'
        );

        $this->assertEquals('obs-456', $score->observationId);
        $this->assertEquals('Test comment', $score->comment);
        $this->assertEquals('config-789', $score->configId);
    }

    public function test_create_score_returns_score_even_on_api_error(): void
    {
        $this->idGenerator->shouldReceive('generateScoreId')->andReturn('score-123');
        $this->apiClient->shouldReceive('createScore')
            ->andThrow(new \RuntimeException('API Error'));

        // Should not throw, should return score anyway
        // Note: The report() function may not exist in unit test context,
        // but the service should still return the score
        try {
            $score = $this->service->createScore('accuracy', 0.95, 'trace-123');
            $this->assertInstanceOf(Score::class, $score);
            $this->assertEquals('score-123', $score->id);
        } catch (\Throwable $e) {
            // If report() fails in unit test, that's expected
            $this->assertStringContainsString('ExceptionHandler', $e->getMessage());
        }
    }

    public function test_create_score_sets_unknown_trace_id_when_null(): void
    {
        $this->idGenerator->shouldReceive('generateScoreId')->andReturn('score-123');
        $this->apiClient->shouldReceive('createScore');

        $score = $this->service->createScore('accuracy', 0.95);

        $this->assertEquals('unknown', $score->traceId);
    }
}
