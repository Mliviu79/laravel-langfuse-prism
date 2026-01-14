<?php

declare(strict_types=1);

namespace Langfuse\Tests\Unit\Enums;

use Langfuse\Support\Enums\ObservationType;
use PHPUnit\Framework\TestCase;

class ObservationTypeTest extends TestCase
{
    public function test_generation_like_types(): void
    {
        $generationLikeTypes = ObservationType::getGenerationLikeTypes();

        $this->assertContains(ObservationType::GENERATION, $generationLikeTypes);
        $this->assertContains(ObservationType::EMBEDDING, $generationLikeTypes);
        $this->assertNotContains(ObservationType::SPAN, $generationLikeTypes);
        $this->assertNotContains(ObservationType::EVENT, $generationLikeTypes);
    }

    public function test_span_like_types(): void
    {
        $spanLikeTypes = ObservationType::getSpanLikeTypes();

        $this->assertContains(ObservationType::SPAN, $spanLikeTypes);
        $this->assertContains(ObservationType::AGENT, $spanLikeTypes);
        $this->assertContains(ObservationType::TOOL, $spanLikeTypes);
        $this->assertNotContains(ObservationType::GENERATION, $spanLikeTypes);
        $this->assertNotContains(ObservationType::EMBEDDING, $spanLikeTypes);
    }

    public function test_type_checking_methods(): void
    {
        $this->assertTrue(ObservationType::GENERATION->isGenerationLike());
        $this->assertFalse(ObservationType::GENERATION->isSpanLike());

        $this->assertTrue(ObservationType::SPAN->isSpanLike());
        $this->assertFalse(ObservationType::SPAN->isGenerationLike());

        $this->assertTrue(ObservationType::EMBEDDING->isGenerationLike());
        $this->assertFalse(ObservationType::EMBEDDING->isSpanLike());
    }

    public function test_descriptions(): void
    {
        $this->assertNotEmpty(ObservationType::SPAN->getDescription());
        $this->assertNotEmpty(ObservationType::GENERATION->getDescription());
        $this->assertNotEmpty(ObservationType::EVENT->getDescription());

        $this->assertStringContainsString('AI/LLM generation', ObservationType::GENERATION->getDescription());
        $this->assertStringContainsString('General-purpose span', ObservationType::SPAN->getDescription());
    }

    public function test_enum_values(): void
    {
        $this->assertEquals('span', ObservationType::SPAN->value);
        $this->assertEquals('generation', ObservationType::GENERATION->value);
        $this->assertEquals('event', ObservationType::EVENT->value);
        $this->assertEquals('agent', ObservationType::AGENT->value);
        $this->assertEquals('tool', ObservationType::TOOL->value);
    }
}
