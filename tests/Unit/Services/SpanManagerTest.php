<?php

declare(strict_types=1);

namespace Langfuse\Tests\Unit\Services;

use Langfuse\Observability\Services\SpanManager;
use Langfuse\Observability\Spans\NullSpan;
use PHPUnit\Framework\TestCase;

class SpanManagerTest extends TestCase
{
    private SpanManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = new SpanManager;
    }

    public function test_register_and_get_span(): void
    {
        $span = new NullSpan;
        $spanId = 'test-span-id';

        $this->manager->registerSpan($spanId, $span);

        $this->assertTrue($this->manager->hasSpan($spanId));
        $this->assertSame($span, $this->manager->getSpan($spanId));
    }

    public function test_get_span_returns_null_for_non_existent_span(): void
    {
        $this->assertNull($this->manager->getSpan('non-existent'));
    }

    public function test_remove_span(): void
    {
        $span = new NullSpan;
        $spanId = 'test-span-id';

        $this->manager->registerSpan($spanId, $span);
        $this->assertTrue($this->manager->hasSpan($spanId));

        $this->manager->removeSpan($spanId);
        $this->assertFalse($this->manager->hasSpan($spanId));
        $this->assertNull($this->manager->getSpan($spanId));
    }

    public function test_get_all_spans(): void
    {
        $span1 = new NullSpan;
        $span2 = new NullSpan;

        $this->manager->registerSpan('span-1', $span1);
        $this->manager->registerSpan('span-2', $span2);

        $allSpans = $this->manager->getAllSpans();

        $this->assertCount(2, $allSpans);
        $this->assertSame($span1, $allSpans['span-1']);
        $this->assertSame($span2, $allSpans['span-2']);
    }

    public function test_clear(): void
    {
        $span = new NullSpan;
        $this->manager->registerSpan('span-1', $span);
        $this->assertTrue($this->manager->hasSpan('span-1'));

        $this->manager->clear();
        $this->assertFalse($this->manager->hasSpan('span-1'));
        $this->assertEmpty($this->manager->getAllSpans());
    }
}
