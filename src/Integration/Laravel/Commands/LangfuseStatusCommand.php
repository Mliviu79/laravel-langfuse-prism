<?php

declare(strict_types=1);

namespace Langfuse\Integration\Laravel\Commands;

use Illuminate\Console\Command;
use Langfuse\Integration\Laravel\DTOs\StatusReportDto;
use Langfuse\Integration\Laravel\Services\StatusReportService;

/**
 * Display Langfuse configuration and status.
 */
class LangfuseStatusCommand extends Command
{
    protected $signature = 'langfuse:status';

    protected $description = 'Display Langfuse configuration and status';

    public function __construct(
        private readonly StatusReportService $statusService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $report = $this->statusService->generateReport();

        $this->displayBasicConfig($report);
        $this->displayOpenTelemetryConfig($report);
        $this->displayAuthentication($report);
        $this->displayWarnings($report);

        return self::SUCCESS;
    }

    private function displayBasicConfig(StatusReportDto $report): void
    {
        $this->info('Langfuse PHP SDK Status');
        $this->newLine();

        $this->components->twoColumnDetail(
            'Tracing Enabled',
            $report->tracingEnabled ? '✓ Yes' : '✗ No'
        );
        $this->components->twoColumnDetail('Environment', $report->environment);
        $this->components->twoColumnDetail(
            'Sample Rate',
            $report->getSampleRatePercentage().'%'
        );

        $this->newLine();
    }

    private function displayOpenTelemetryConfig(StatusReportDto $report): void
    {
        $this->info('OpenTelemetry Configuration');
        $this->newLine();

        $otelConfig = $report->otelConfig;

        $this->components->twoColumnDetail('Endpoint', $otelConfig->endpoint);
        $this->components->twoColumnDetail('Protocol', $otelConfig->protocol);

        $this->components->twoColumnDetail(
            'Span Processor',
            sprintf(
                '<fg=green>%s</> <fg=gray>%s</>',
                $report->getProcessorType(),
                $report->getProcessorReason()
            )
        );

        $this->components->twoColumnDetail('PHP SAPI', $report->phpSapi);
        $this->components->twoColumnDetail(
            'Running in Console',
            $report->runningInConsole ? '✓ Yes' : '✗ No'
        );

        if (! $otelConfig->useSimpleProcessor) {
            $this->newLine();
            $this->components->twoColumnDetail('Max Queue Size', number_format($otelConfig->maxQueueSize));
            $this->components->twoColumnDetail('Schedule Delay', $otelConfig->scheduledDelayMillis.'ms');
            $this->components->twoColumnDetail('Export Timeout', $otelConfig->exportTimeoutMillis.'ms');
            $this->components->twoColumnDetail('Max Batch Size', number_format($otelConfig->maxExportBatchSize));
        }

        $this->newLine();
        $this->components->twoColumnDetail(
            'Compression',
            $otelConfig->compression ? '✓ Enabled' : '✗ Disabled'
        );
        $this->components->twoColumnDetail('Service Name', $otelConfig->serviceName);
        $this->components->twoColumnDetail('Service Version', $otelConfig->serviceVersion);

        $this->newLine();
    }

    private function displayAuthentication(StatusReportDto $report): void
    {
        $this->info('Authentication');
        $this->newLine();

        $this->components->twoColumnDetail(
            'Public Key',
            $report->hasPublicKey ? '✓ Configured' : '✗ Missing'
        );
        $this->components->twoColumnDetail(
            'Secret Key',
            $report->hasSecretKey ? '✓ Configured' : '✗ Missing'
        );

        $this->newLine();
    }

    private function displayWarnings(StatusReportDto $report): void
    {
        if (! $report->hasWarnings()) {
            return;
        }

        foreach ($report->warnings as $warning) {
            $this->components->warn($warning);
        }
    }
}
