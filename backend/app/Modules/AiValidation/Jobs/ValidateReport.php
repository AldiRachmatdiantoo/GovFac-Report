<?php

namespace App\Modules\AiValidation\Jobs;

use App\Modules\AiValidation\Services\AiValidationService;
use App\Modules\Report\Models\Report;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ValidateReport implements ShouldQueue
{
    use Queueable;

    // Three attempts of at most 15 s with short pauses keep the worst case
    // under the 60 s the PRD allows before we fall back to `flag`.
    public int $tries = 3;

    public int $timeout = 15;

    public function __construct(
        public Report $report,
        public int $revision,
    ) {}

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [2, 5];
    }

    public function handle(AiValidationService $validations): void
    {
        $validations->validate($this->report, $this->revision);
    }

    public function failed(?Throwable $exception): void
    {
        app(AiValidationService::class)->recordUnavailable($this->report->refresh(), $this->revision);
    }
}
