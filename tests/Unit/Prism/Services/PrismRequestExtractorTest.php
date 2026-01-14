<?php

declare(strict_types=1);

namespace Langfuse\Tests\Unit\Prism\Services;

use Langfuse\Integration\Prism\DTOs\PrismRequestDto;
use Langfuse\Integration\Prism\Services\PrismRequestExtractor;
use PHPUnit\Framework\TestCase;

/**
 * Tests for PrismRequestExtractor.
 *
 * Note: Due to how the extractor uses instanceof checks, we test the DTO
 * construction rather than the full extraction logic, which requires
 * real Prism request objects.
 */
class PrismRequestExtractorTest extends TestCase
{
    private PrismRequestExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extractor = new PrismRequestExtractor;
    }

    public function test_extractor_can_be_instantiated(): void
    {
        $extractor = new PrismRequestExtractor;
        $this->assertInstanceOf(PrismRequestExtractor::class, $extractor);
    }

    public function test_request_dto_can_hold_all_values(): void
    {
        $dto = new PrismRequestDto(
            provider: 'openai',
            model: 'gpt-4',
            prompt: 'Hello, world!',
            messages: [['role' => 'user', 'content' => 'Hello']],
            systemPrompt: 'You are helpful',
            parameters: ['temperature' => 0.7, 'max_tokens' => 100],
            requestId: 'req-123',
            userData: ['user_id' => 'user-1'],
            additionalInput: ['key' => 'value'],
        );

        $this->assertEquals('openai', $dto->provider);
        $this->assertEquals('gpt-4', $dto->model);
        $this->assertEquals('Hello, world!', $dto->prompt);
        $this->assertIsArray($dto->messages);
        $this->assertEquals('You are helpful', $dto->systemPrompt);
        $this->assertEquals(['temperature' => 0.7, 'max_tokens' => 100], $dto->parameters);
        $this->assertEquals('req-123', $dto->requestId);
        $this->assertEquals(['user_id' => 'user-1'], $dto->userData);
        $this->assertEquals(['key' => 'value'], $dto->additionalInput);
    }

    public function test_request_dto_handles_null_values(): void
    {
        $dto = new PrismRequestDto(
            provider: 'openai',
            model: 'gpt-4',
        );

        $this->assertEquals('openai', $dto->provider);
        $this->assertEquals('gpt-4', $dto->model);
        $this->assertNull($dto->prompt);
        $this->assertNull($dto->messages);
        $this->assertNull($dto->systemPrompt);
        $this->assertNull($dto->parameters);
        $this->assertNull($dto->requestId);
        $this->assertNull($dto->userData);
        $this->assertNull($dto->additionalInput);
    }

    public function test_request_dto_with_messages_array(): void
    {
        $messages = [
            ['role' => 'user', 'content' => 'Hello'],
            ['role' => 'assistant', 'content' => 'Hi there!'],
            ['role' => 'user', 'content' => 'How are you?'],
        ];

        $dto = new PrismRequestDto(
            provider: 'anthropic',
            model: 'claude-3-opus',
            messages: $messages,
        );

        $this->assertCount(3, $dto->messages);
        $this->assertEquals('user', $dto->messages[0]['role']);
        $this->assertEquals('assistant', $dto->messages[1]['role']);
    }

    public function test_request_dto_with_parameters(): void
    {
        $parameters = [
            'temperature' => 0.7,
            'max_tokens' => 500,
            'top_p' => 0.95,
            'frequency_penalty' => 0.5,
        ];

        $dto = new PrismRequestDto(
            provider: 'openai',
            model: 'gpt-4',
            parameters: $parameters,
        );

        $this->assertEquals(0.7, $dto->parameters['temperature']);
        $this->assertEquals(500, $dto->parameters['max_tokens']);
        $this->assertEquals(0.95, $dto->parameters['top_p']);
        $this->assertEquals(0.5, $dto->parameters['frequency_penalty']);
    }

    public function test_request_dto_with_additional_input_for_embeddings(): void
    {
        $dto = new PrismRequestDto(
            provider: 'openai',
            model: 'text-embedding-3-small',
            additionalInput: [
                'inputs' => ['text1', 'text2', 'text3'],
                'has_images' => false,
            ],
        );

        $this->assertEquals(['text1', 'text2', 'text3'], $dto->additionalInput['inputs']);
        $this->assertFalse($dto->additionalInput['has_images']);
    }

    public function test_request_dto_with_additional_input_for_moderation(): void
    {
        $dto = new PrismRequestDto(
            provider: 'openai',
            model: 'text-moderation-latest',
            additionalInput: [
                'inputs' => ['Check this content'],
            ],
        );

        $this->assertEquals(['Check this content'], $dto->additionalInput['inputs']);
    }

    public function test_request_dto_with_user_data(): void
    {
        $dto = new PrismRequestDto(
            provider: 'openai',
            model: 'gpt-4',
            userData: [
                'user_id' => 'user-123',
                'session_id' => 'session-456',
                'custom_field' => 'custom_value',
            ],
        );

        $this->assertEquals('user-123', $dto->userData['user_id']);
        $this->assertEquals('session-456', $dto->userData['session_id']);
    }
}
