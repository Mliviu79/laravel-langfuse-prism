<?php

declare(strict_types=1);

namespace Langfuse\Tests\Unit\DTOs;

use Langfuse\Client\DTOs\SpanCreationDto;
use Langfuse\Support\Enums\ObservationType;
use Langfuse\Support\Enums\SpanLevel;
use PHPUnit\Framework\TestCase;

class SpanCreationDtoTest extends TestCase
{
    public function test_from_array(): void
    {
        $data = [
            'name' => 'test-span',
            'type' => ObservationType::GENERATION,
            'input' => ['test' => 'data'],
            'output' => 'result',
            'metadata' => ['key' => 'value'],
            'version' => '1.0',
            'level' => SpanLevel::INFO,
            'statusMessage' => 'Success',
            'parentId' => 'parent-123',
            'model' => 'gpt-4',
        ];

        $dto = SpanCreationDto::fromArray($data);

        $this->assertSame('test-span', $dto->name);
        $this->assertSame(ObservationType::GENERATION, $dto->type);
        $this->assertSame(['test' => 'data'], $dto->input);
        $this->assertSame('result', $dto->output);
        $this->assertSame(['key' => 'value'], $dto->metadata);
        $this->assertSame('1.0', $dto->version);
        $this->assertSame(SpanLevel::INFO, $dto->level);
        $this->assertSame('Success', $dto->statusMessage);
        $this->assertSame('parent-123', $dto->parentId);
        $this->assertSame('gpt-4', $dto->model);
    }

    public function test_from_array_with_defaults(): void
    {
        $data = ['name' => 'test-span'];
        $dto = SpanCreationDto::fromArray($data);

        $this->assertSame('test-span', $dto->name);
        $this->assertSame(ObservationType::SPAN, $dto->type);
        $this->assertNull($dto->input);
        $this->assertNull($dto->output);
    }

    public function test_to_array(): void
    {
        $dto = new SpanCreationDto(
            name: 'test-span',
            type: ObservationType::GENERATION,
            input: ['test' => 'data'],
            output: 'result',
            metadata: ['key' => 'value'],
            version: '1.0',
            level: SpanLevel::INFO,
            statusMessage: 'Success',
            parentId: 'parent-123',
            model: 'gpt-4',
        );

        $array = $dto->toArray();

        $this->assertSame('test-span', $array['name']);
        $this->assertSame(ObservationType::GENERATION, $array['type']);
        $this->assertSame(['test' => 'data'], $array['input']);
        $this->assertSame('result', $array['output']);
        $this->assertSame(['key' => 'value'], $array['metadata']);
        $this->assertSame('1.0', $array['version']);
        $this->assertSame(SpanLevel::INFO, $array['level']);
        $this->assertSame('Success', $array['statusMessage']);
        $this->assertSame('parent-123', $array['parentId']);
        $this->assertSame('gpt-4', $array['model']);
    }

    public function test_to_array_filters_nulls(): void
    {
        $dto = new SpanCreationDto(name: 'test-span');
        $array = $dto->toArray();

        $this->assertArrayNotHasKey('input', $array);
        $this->assertArrayNotHasKey('output', $array);
        $this->assertArrayNotHasKey('metadata', $array);
    }
}
