<?php

declare(strict_types=1);

namespace Langfuse\Tests\Unit\Services;

use Langfuse\Observability\Services\AttributeMapperService;
use Langfuse\Support\Enums\ObservationType;
use OpenTelemetry\API\Trace\SpanKind;
use PHPUnit\Framework\TestCase;

class AttributeMapperServiceTest extends TestCase
{
    private AttributeMapperService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AttributeMapperService();
    }

    public function testMapGenerationToClientKind(): void
    {
        $kind = $this->service->mapObservationTypeToSpanKind(ObservationType::GENERATION);
        $this->assertSame(SpanKind::KIND_CLIENT, $kind);
    }

    public function testMapEmbeddingToClientKind(): void
    {
        $kind = $this->service->mapObservationTypeToSpanKind(ObservationType::EMBEDDING);
        $this->assertSame(SpanKind::KIND_CLIENT, $kind);
    }

    public function testMapAgentToInternalKind(): void
    {
        $kind = $this->service->mapObservationTypeToSpanKind(ObservationType::AGENT);
        $this->assertSame(SpanKind::KIND_INTERNAL, $kind);
    }

    public function testMapToolToInternalKind(): void
    {
        $kind = $this->service->mapObservationTypeToSpanKind(ObservationType::TOOL);
        $this->assertSame(SpanKind::KIND_INTERNAL, $kind);
    }

    public function testMapChainToInternalKind(): void
    {
        $kind = $this->service->mapObservationTypeToSpanKind(ObservationType::CHAIN);
        $this->assertSame(SpanKind::KIND_INTERNAL, $kind);
    }

    public function testMapRetrieverToClientKind(): void
    {
        $kind = $this->service->mapObservationTypeToSpanKind(ObservationType::RETRIEVER);
        $this->assertSame(SpanKind::KIND_CLIENT, $kind);
    }

    public function testMapSpanToInternalKind(): void
    {
        $kind = $this->service->mapObservationTypeToSpanKind(ObservationType::SPAN);
        $this->assertSame(SpanKind::KIND_INTERNAL, $kind);
    }
}
