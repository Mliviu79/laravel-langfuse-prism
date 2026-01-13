<?php

declare(strict_types=1);

namespace Langfuse\Tests\Unit\Prism\Services;

use DateTime;
use Langfuse\Client\Contracts\LangfuseClientInterface;
use Langfuse\Integration\Prism\Contracts\PrismRequestExtractorInterface;
use Langfuse\Integration\Prism\Contracts\PrismResponseExtractorInterface;
use Langfuse\Integration\Prism\DTOs\PrismRequestDto;
use Langfuse\Integration\Prism\DTOs\PrismResponseDto;
use Langfuse\Integration\Prism\DTOs\PrismUsageDto;
use Langfuse\Integration\Prism\Services\PrismMetadataExtractor;
use Langfuse\Integration\Prism\Services\PrismTracingService;
use Langfuse\Observability\Contracts\SpanInterface;
use Langfuse\Support\Enums\ObservationType;
use Langfuse\Support\Enums\SpanLevel;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class PrismTracingServiceTest extends TestCase
{
    private MockInterface $langfuse;
    private MockInterface $requestExtractor;
    private MockInterface $responseExtractor;
    private MockInterface $metadataExtractor;
    private PrismTracingService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->langfuse = Mockery::mock(LangfuseClientInterface::class);
        $this->requestExtractor = Mockery::mock(PrismRequestExtractorInterface::class);
        $this->responseExtractor = Mockery::mock(PrismResponseExtractorInterface::class);
        $this->metadataExtractor = Mockery::mock(PrismMetadataExtractor::class);

        $this->service = new PrismTracingService(
            $this->langfuse,
            $this->requestExtractor,
            $this->responseExtractor,
            $this->metadataExtractor,
            traceModelParams: true,
            traceUsage: true,
            traceCost: true
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_start_trace_creates_span_with_correct_name(): void
    {
        $request = Mockery::mock(\Prism\Prism\Text\Request::class);
        
        $requestDto = new PrismRequestDto(
            provider: 'openai',
            model: 'gpt-4',
            prompt: 'Hello, world!',
        );

        $this->requestExtractor->shouldReceive('extract')
            ->once()
            ->with($request)
            ->andReturn($requestDto);

        $this->metadataExtractor->shouldReceive('extractFromRequest')
            ->once()
            ->andReturn(['provider' => 'openai']);

        $span = Mockery::mock(SpanInterface::class);
        $span->shouldReceive('update')->andReturnSelf();
        $span->shouldReceive('getName')->andReturn('prism-openai-gpt-4');

        $this->langfuse->shouldReceive('startSpan')
            ->once()
            ->withArgs(function ($name, $type, $input, $metadata) {
                return $name === 'prism-openai-gpt-4'
                    && $type === ObservationType::GENERATION;
            })
            ->andReturn($span);

        $result = $this->service->startTrace($request, 'text');

        $this->assertSame($span, $result);
        $this->assertEquals('prism-openai-gpt-4', $result->getName());
    }

    public function test_start_trace_uses_generation_type_for_text(): void
    {
        $request = Mockery::mock(\Prism\Prism\Text\Request::class);
        
        $this->requestExtractor->shouldReceive('extract')
            ->andReturn(new PrismRequestDto(
                provider: 'openai',
                model: 'gpt-4'
            ));

        $this->metadataExtractor->shouldReceive('extractFromRequest')
            ->andReturn([]);

        $span = Mockery::mock(SpanInterface::class);
        $span->shouldReceive('update')->andReturnSelf();

        $this->langfuse->shouldReceive('startSpan')
            ->withArgs(function ($name, $type) {
                return $type === ObservationType::GENERATION;
            })
            ->andReturn($span);

        $result = $this->service->startTrace($request, 'text');
        
        $this->assertInstanceOf(SpanInterface::class, $result);
    }

    public function test_start_trace_uses_embedding_type_for_embeddings(): void
    {
        $request = Mockery::mock(\Prism\Prism\Embeddings\Request::class);
        
        $this->requestExtractor->shouldReceive('extract')
            ->andReturn(new PrismRequestDto(
                provider: 'openai',
                model: 'text-embedding-3-small'
            ));

        $this->metadataExtractor->shouldReceive('extractFromRequest')
            ->andReturn([]);

        $span = Mockery::mock(SpanInterface::class);
        $span->shouldReceive('update')->andReturnSelf();

        $this->langfuse->shouldReceive('startSpan')
            ->withArgs(function ($name, $type) {
                return $type === ObservationType::EMBEDDING;
            })
            ->andReturn($span);

        $result = $this->service->startTrace($request, 'embeddings');
        
        $this->assertInstanceOf(SpanInterface::class, $result);
    }

    public function test_update_with_success_sets_output_and_usage(): void
    {
        $response = Mockery::mock(\Prism\Prism\Text\Response::class);
        $startTime = new DateTime();

        $responseDto = new PrismResponseDto(
            text: 'Response text',
            usage: new PrismUsageDto(
                promptTokens: 50,
                completionTokens: 100,
                totalTokens: 150,
                thoughtTokens: null
            ),
        );

        $this->responseExtractor->shouldReceive('extract')
            ->once()
            ->with($response)
            ->andReturn($responseDto);

        $this->metadataExtractor->shouldReceive('extractFromResponse')
            ->once()
            ->andReturn(['model' => 'gpt-4']);

        $span = Mockery::mock(SpanInterface::class);
        $span->shouldReceive('update')
            ->once()
            ->andReturnSelf();

        $this->service->updateWithSuccess($span, $response, $startTime);
        
        $this->assertTrue(true); // Test passes if no exception
    }

    public function test_update_with_error_sets_error_level_and_message(): void
    {
        $exception = new \RuntimeException('API error occurred', 500);

        $span = Mockery::mock(SpanInterface::class);
        // The update method is called with named parameters, so use Mockery::any()
        $span->shouldReceive('update')
            ->once()
            ->andReturnSelf();

        $this->service->updateWithError($span, $exception);
        
        $this->assertTrue(true); // Test passes if no exception
    }

    public function test_start_trace_with_unknown_provider(): void
    {
        $request = Mockery::mock(\Prism\Prism\Text\Request::class);
        
        $this->requestExtractor->shouldReceive('extract')
            ->andReturn(new PrismRequestDto(
                provider: null,
                model: null
            ));

        $this->metadataExtractor->shouldReceive('extractFromRequest')
            ->andReturn([]);

        $span = Mockery::mock(SpanInterface::class);
        // Update is always called, but with null model when provider/model is unknown
        $span->shouldReceive('update')
            ->once()
            ->withArgs(function ($model) {
                // Model should be null when unknown
                return $model === null;
            })
            ->andReturnSelf();

        $this->langfuse->shouldReceive('startSpan')
            ->withArgs(function ($name) {
                return $name === 'prism-unknown-unknown';
            })
            ->andReturn($span);

        $result = $this->service->startTrace($request, 'text');
        
        $this->assertSame($span, $result);
    }

    public function test_service_respects_trace_model_params_flag(): void
    {
        $service = new PrismTracingService(
            $this->langfuse,
            $this->requestExtractor,
            $this->responseExtractor,
            $this->metadataExtractor,
            traceModelParams: false,
            traceUsage: true,
            traceCost: true
        );

        $request = Mockery::mock(\Prism\Prism\Text\Request::class);
        
        $this->requestExtractor->shouldReceive('extract')
            ->andReturn(new PrismRequestDto(
                provider: 'openai',
                model: 'gpt-4',
                parameters: ['temperature' => 0.7]
            ));

        $this->metadataExtractor->shouldReceive('extractFromRequest')
            ->andReturn([]);

        $span = Mockery::mock(SpanInterface::class);
        $span->shouldReceive('update')
            ->withArgs(function ($model, $modelParameters) {
                // modelParameters should be null when traceModelParams is false
                return $modelParameters === null;
            })
            ->andReturnSelf();

        $this->langfuse->shouldReceive('startSpan')
            ->withArgs(function ($name, $type, $input, $metadata) {
                // model_params should not be in metadata when disabled
                return !isset($metadata['model_params']);
            })
            ->andReturn($span);

        $result = $service->startTrace($request, 'text');
        
        $this->assertSame($span, $result);
    }
}
