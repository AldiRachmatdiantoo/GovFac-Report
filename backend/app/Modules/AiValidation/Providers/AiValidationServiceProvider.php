<?php

namespace App\Modules\AiValidation\Providers;

use App\Modules\AiValidation\Contracts\AiValidator;
use App\Modules\AiValidation\Listeners\QueueReportValidation;
use App\Modules\AiValidation\Services\RuleBasedAiValidator;
use App\Modules\Report\Events\ReportSubmitted;
use App\Modules\Shared\Providers\ModuleServiceProvider;
use Illuminate\Support\Facades\Event;

class AiValidationServiceProvider extends ModuleServiceProvider
{
    /**
     * Daftarkan binding Contracts => implementasi milik modul ini.
     *
     * @var array<class-string, class-string>
     */
    protected array $contracts = [
        AiValidator::class => RuleBasedAiValidator::class,
    ];

    public function boot(): void
    {
        parent::boot();

        Event::listen(ReportSubmitted::class, QueueReportValidation::class);
    }
}
