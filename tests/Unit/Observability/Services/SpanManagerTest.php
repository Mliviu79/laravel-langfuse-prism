<?php

declare(strict_types=1);

namespace Langfuse\Tests\Unit\Observability\Services;

use Langfuse\Observability\Contracts\SpanInterface;
use Langfuse\Observability\Services\SpanManager;
use Langfuse\Observability\Spans\NullSpan;
use Mockery;
use PHPUnit\Framework\TestCase;

class SpanManagerTest extends TestCase
{
    private SpanManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = new SpanManager();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_register_span_stores_span(): void
    {
        $span = new NullSpan();
        
        $this->manager->registerSpan('span-1', $span);

        $this->assertTrue($this->manager->hasSpan('span-1'));
    }

    public function test_get_span_returns_registered_span(): void
    {
        $span = new NullSpan();
        $this->manager->registerSpan('span-1', $span);

        $result = $this->manager->getSpan('span-1');

        $this->assertSame($span, $result);
    }

    public function test_get_span_returns_null_for_unknown_id(): void
    {
        $result = $this->manager->getSpan('unknown-id');

        $this->assertNull($result);
    }

    public function test_remove_span_deletes_span(): void
    {
        $span = new NullSpan();
        $this->manager->registerSpan('span-1', $span);
        
        $this->manager->removeSpan('span-1');

        $this->assertFalse($this->manager->hasSpan('span-1'));
        $this->assertNull($this->manager->getSpan('span-1'));
    }

    public function test_has_span_returns_true_for_registered_span(): void
    {
        $span = new NullSpan();
        $this->manager->registerSpan('span-1', $span);

        $this->assertTrue($this->manager->hasSpan('span-1'));
    }

    public function test_has_span_returns_false_for_unknown_span(): void
    {
        $this->assertFalse($this->manager->hasSpan('unknown'));
    }

    public function test_get_all_spans_returns_all_registered_spans(): void
    {
        $span1 = new NullSpan();
        $span2 = new NullSpan();
        
        $this->manager->registerSpan('span-1', $span1);
        $this->manager->registerSpan('span-2', $span2);

        $allSpans = $this->manager->getAllSpans();

        $this->assertCount(2, $allSpans);
        $this->assertSame($span1, $allSpans['span-1']);
        $this->assertSame($span2, $allSpans['span-2']);
    }

    public function test_clear_removes_all_spans(): void
    {
        $this->manager->registerSpan('span-1', new NullSpan());
        $this->manager->registerSpan('span-2', new NullSpan());
        
        $this->manager->clear();

        $this->assertEmpty($this->manager->getAllSpans());
        $this->assertFalse($this->manager->hasSpan('span-1'));
        $this->assertFalse($this->manager->hasSpan('span-2'));
    }

    public function test_multiple_spans_with_different_ids(): void
    {
        $span1 = Mockery::mock(SpanInterface::class);
        $span2 = Mockery::mock(SpanInterface::class);
        $span3 = Mockery::mock(SpanInterface::class);

        $this->manager->registerSpan('id-1', $span1);
        $this->manager->registerSpan('id-2', $span2);
        $this->manager->registerSpan('id-3', $span3);

        $this->assertSame($span1, $this->manager->getSpan('id-1'));
        $this->assertSame($span2, $this->manager->getSpan('id-2'));
        $this->assertSame($span3, $this->manager->getSpan('id-3'));
    }

    public function test_registering_same_id_overwrites_span(): void
    {
        $span1 = new NullSpan();
        $span2 = new NullSpan();

        $this->manager->registerSpan('same-id', $span1);
        $this->manager->registerSpan('same-id', $span2);

        $this->assertSame($span2, $this->manager->getSpan('same-id'));
        $this->assertCount(1, $this->manager->getAllSpans());
    }
}
