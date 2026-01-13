<?php

declare(strict_types=1);

namespace Langfuse\Integration\Laravel\Queue\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Langfuse\Client\Contracts\LangfuseClientInterface;

/**
 * Job for flushing spans asynchronously
 */
class FlushSpansJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job
     */
    public function handle(LangfuseClientInterface $langfuse): void
    {
        $langfuse->flush();
    }
}
