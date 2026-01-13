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
        $this->service = new IdGeneratorService();
    }

    public function testGenerateIdReturnsValidUuid(): void
    {
        $id = $this->service->generateId();
        $this->assertTrue(Uuid::isValid($id));
    }

    public function testGenerateTraceIdReturnsValidUuid(): void
    {
        $id = $this->service->generateTraceId();
        $this->assertTrue(Uuid::isValid($id));
    }

    public function testGenerateObservationIdReturnsValidUuid(): void
    {
        $id = $this->service->generateObservationId();
        $this->assertTrue(Uuid::isValid($id));
    }

    public function testGenerateScoreIdReturnsValidUuid(): void
    {
        $id = $this->service->generateScoreId();
        $this->assertTrue(Uuid::isValid($id));
    }

    public function testGenerateTimestampReturnsIso8601Format(): void
    {
        $timestamp = $this->service->generateTimestamp();
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $timestamp);
    }

    public function testIsValidUuidReturnsTrueForValidUuid(): void
    {
        $validUuid = Uuid::uuid4()->toString();
        $this->assertTrue($this->service->isValidUuid($validUuid));
    }

    public function testIsValidUuidReturnsFalseForInvalidUuid(): void
    {
        $this->assertFalse($this->service->isValidUuid('invalid-uuid'));
        $this->assertFalse($this->service->isValidUuid('123'));
        $this->assertFalse($this->service->isValidUuid(''));
    }

    public function testGeneratedIdsAreUnique(): void
    {
        $ids = [];
        for ($i = 0; $i < 100; $i++) {
            $ids[] = $this->service->generateId();
        }

        $this->assertCount(100, array_unique($ids));
    }
}
