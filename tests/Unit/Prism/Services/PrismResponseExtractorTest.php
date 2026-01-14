<?php

declare(strict_types=1);

namespace Langfuse\Tests\Unit\Prism\Services;

use DateTime;
use Langfuse\Integration\Prism\DTOs\PrismCostDto;
use Langfuse\Integration\Prism\DTOs\PrismResponseDto;
use Langfuse\Integration\Prism\DTOs\PrismUsageDto;
use Langfuse\Integration\Prism\Services\PrismResponseExtractor;
use PHPUnit\Framework\TestCase;

/**
 * Tests for PrismResponseExtractor.
 *
 * Note: Due to how the extractor uses instanceof checks, we test the DTO
 * construction rather than the full extraction logic, which requires
 * real Prism response objects.
 */
class PrismResponseExtractorTest extends TestCase
{
    private PrismResponseExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extractor = new PrismResponseExtractor;
    }

    public function test_extractor_can_be_instantiated(): void
    {
        $extractor = new PrismResponseExtractor;
        $this->assertInstanceOf(PrismResponseExtractor::class, $extractor);
    }

    public function test_response_dto_can_hold_all_values(): void
    {
        $usage = new PrismUsageDto(
            promptTokens: 50,
            completionTokens: 100,
            totalTokens: 150,
            thoughtTokens: 10
        );

        $cost = new PrismCostDto(
            inputCost: 0.0005,
            outputCost: 0.0015,
            totalCost: 0.002
        );

        $dto = new PrismResponseDto(
            text: 'Response text',
            message: ['role' => 'assistant', 'content' => 'Response'],
            choices: [['text' => 'Choice 1', 'finish_reason' => 'stop']],
            usage: $usage,
            cost: $cost,
            metadata: ['model' => 'gpt-4', 'finish_reason' => 'stop'],
            completionStartTime: new DateTime,
            responseTime: 1.5,
            additionalOutput: ['tool_calls' => []],
        );

        $this->assertEquals('Response text', $dto->text);
        $this->assertEquals(['role' => 'assistant', 'content' => 'Response'], $dto->message);
        $this->assertCount(1, $dto->choices);
        $this->assertSame($usage, $dto->usage);
        $this->assertSame($cost, $dto->cost);
        $this->assertEquals('gpt-4', $dto->metadata['model']);
        $this->assertInstanceOf(DateTime::class, $dto->completionStartTime);
        $this->assertEquals(1.5, $dto->responseTime);
    }

    public function test_response_dto_handles_null_values(): void
    {
        $dto = new PrismResponseDto(
            text: 'Simple response',
        );

        $this->assertEquals('Simple response', $dto->text);
        $this->assertNull($dto->message);
        $this->assertNull($dto->choices);
        $this->assertNull($dto->usage);
        $this->assertNull($dto->cost);
        $this->assertNull($dto->metadata);
        $this->assertNull($dto->completionStartTime);
        $this->assertNull($dto->responseTime);
        $this->assertNull($dto->additionalOutput);
    }

    public function test_usage_dto_calculates_total(): void
    {
        $usage = new PrismUsageDto(
            promptTokens: 50,
            completionTokens: 100,
            totalTokens: 150,
            thoughtTokens: null
        );

        $this->assertEquals(50, $usage->promptTokens);
        $this->assertEquals(100, $usage->completionTokens);
        $this->assertEquals(150, $usage->totalTokens);
        $this->assertNull($usage->thoughtTokens);
    }

    public function test_usage_dto_with_thought_tokens(): void
    {
        $usage = new PrismUsageDto(
            promptTokens: 50,
            completionTokens: 100,
            totalTokens: 150,
            thoughtTokens: 25
        );

        $this->assertEquals(25, $usage->thoughtTokens);
    }

    public function test_cost_dto_holds_all_values(): void
    {
        $cost = new PrismCostDto(
            inputCost: 0.001,
            outputCost: 0.003,
            totalCost: 0.004
        );

        $this->assertEquals(0.001, $cost->inputCost);
        $this->assertEquals(0.003, $cost->outputCost);
        $this->assertEquals(0.004, $cost->totalCost);
    }

    public function test_response_dto_with_choices(): void
    {
        $choices = [
            ['text' => 'Choice 1', 'finish_reason' => 'stop'],
            ['text' => 'Choice 2', 'finish_reason' => 'length'],
        ];

        $dto = new PrismResponseDto(
            text: 'Primary response',
            choices: $choices,
        );

        $this->assertCount(2, $dto->choices);
        $this->assertEquals('Choice 1', $dto->choices[0]['text']);
        $this->assertEquals('length', $dto->choices[1]['finish_reason']);
    }

    public function test_response_dto_with_tool_calls(): void
    {
        $dto = new PrismResponseDto(
            text: '',
            additionalOutput: [
                'tool_calls' => [
                    ['name' => 'get_weather', 'arguments' => ['location' => 'NYC']],
                    ['name' => 'get_time', 'arguments' => ['timezone' => 'EST']],
                ],
            ],
        );

        $this->assertArrayHasKey('tool_calls', $dto->additionalOutput);
        $this->assertCount(2, $dto->additionalOutput['tool_calls']);
    }

    public function test_response_dto_with_embeddings_output(): void
    {
        $dto = new PrismResponseDto(
            additionalOutput: [
                'embeddings_count' => 5,
            ],
        );

        $this->assertEquals(5, $dto->additionalOutput['embeddings_count']);
    }

    public function test_response_dto_with_moderation_output(): void
    {
        $dto = new PrismResponseDto(
            metadata: [
                'flagged' => true,
                'flagged_count' => 2,
            ],
            additionalOutput: [
                'results_count' => 3,
            ],
        );

        $this->assertTrue($dto->metadata['flagged']);
        $this->assertEquals(2, $dto->metadata['flagged_count']);
        $this->assertEquals(3, $dto->additionalOutput['results_count']);
    }

    public function test_response_dto_with_structured_output(): void
    {
        $structuredData = ['name' => 'John', 'age' => 30, 'email' => 'john@example.com'];

        $dto = new PrismResponseDto(
            text: '{"name": "John", "age": 30, "email": "john@example.com"}',
            additionalOutput: [
                'structured_output' => $structuredData,
            ],
        );

        $this->assertEquals($structuredData, $dto->additionalOutput['structured_output']);
    }

    public function test_response_dto_with_images_output(): void
    {
        $dto = new PrismResponseDto(
            additionalOutput: [
                'images_count' => 4,
            ],
        );

        $this->assertEquals(4, $dto->additionalOutput['images_count']);
    }
}
