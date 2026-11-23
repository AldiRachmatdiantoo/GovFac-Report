<?php

namespace App\Modules\AiValidation\Events;

use App\Modules\AiValidation\Models\AiValidation;
use App\Modules\Report\Models\Report;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReportValidated implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Report $report,
        public AiValidation $validation,
    ) {}
}
