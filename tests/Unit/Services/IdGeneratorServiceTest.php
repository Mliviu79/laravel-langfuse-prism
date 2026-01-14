<?php

declare(strict_types=1);

namespace Langfuse\Tests\Unit\Services;

use Langfuse\Support\Services\IdGeneratorService;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class IdGeneratorServiceTest extends TestCase
{
    private IdGeneratorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new IdGeneratorService;
    }

    public function test_generate_id_returns_valid_uuid(): void
    {
        $id = $this->service->generateId();
        $this->assertTrue(Uuid::isValid($id));
    }

    public function test_generate_trace_id_returns_valid_uuid(): void
    {
        $id = $this->service->generateTraceId();
        $this->assertTrue(Uuid::isValid($id));
    }

    public function test_generate_observation_id_returns_valid_uuid(): void
    {
        $id = $this->service->generateObservationId();
        $this->assertTrue(Uuid::isValid($id));
    }

    public function test_generate_score_id_returns_valid_uuid(): void
    {
        $id = $this->service->generateScoreId();
        $this->assertTrue(Uuid::isValid($id));
    }

    public function test_generate_timestamp_returns_iso8601_format(): void
    {
        $timestamp = $this->service->generateTimestamp();
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $timestamp);
    }

    public function test_is_valid_uuid_returns_true_for_valid_uuid(): void
    {
        $validUuid = Uuid::uuid4()->toString();
        $this->assertTrue($this->service->isValidUuid($validUuid));
    }

    public function test_is_valid_uuid_returns_false_for_invalid_uuid(): void
    {
        $this->assertFalse($this->service->isValidUuid('invalid-uuid'));
        $this->assertFalse($this->service->isValidUuid('123'));
        $this->assertFalse($this->service->isValidUuid(''));
    }

    public function test_generated_ids_are_unique(): void
    {
        $ids = [];
        for ($i = 0; $i < 100; $i++) {
            $ids[] = $this->service->generateId();
        }

        $this->assertCount(100, array_unique($ids));
    }
}
