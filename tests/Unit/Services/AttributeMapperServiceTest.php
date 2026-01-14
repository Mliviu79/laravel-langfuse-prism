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
        $this->service = new AttributeMapperService;
    }

    public function test_map_generation_to_client_kind(): void
    {
        $kind = $this->service->mapObservationTypeToSpanKind(ObservationType::GENERATION);
        $this->assertSame(SpanKind::KIND_CLIENT, $kind);
    }

    public function test_map_embedding_to_client_kind(): void
    {
        $kind = $this->service->mapObservationTypeToSpanKind(ObservationType::EMBEDDING);
        $this->assertSame(SpanKind::KIND_CLIENT, $kind);
    }

    public function test_map_agent_to_internal_kind(): void
    {
        $kind = $this->service->mapObservationTypeToSpanKind(ObservationType::AGENT);
        $this->assertSame(SpanKind::KIND_INTERNAL, $kind);
    }

    public function test_map_tool_to_internal_kind(): void
    {
        $kind = $this->service->mapObservationTypeToSpanKind(ObservationType::TOOL);
        $this->assertSame(SpanKind::KIND_INTERNAL, $kind);
    }

    public function test_map_chain_to_internal_kind(): void
    {
        $kind = $this->service->mapObservationTypeToSpanKind(ObservationType::CHAIN);
        $this->assertSame(SpanKind::KIND_INTERNAL, $kind);
    }

    public function test_map_retriever_to_client_kind(): void
    {
        $kind = $this->service->mapObservationTypeToSpanKind(ObservationType::RETRIEVER);
        $this->assertSame(SpanKind::KIND_CLIENT, $kind);
    }

    public function test_map_span_to_internal_kind(): void
    {
        $kind = $this->service->mapObservationTypeToSpanKind(ObservationType::SPAN);
        $this->assertSame(SpanKind::KIND_INTERNAL, $kind);
    }
}
