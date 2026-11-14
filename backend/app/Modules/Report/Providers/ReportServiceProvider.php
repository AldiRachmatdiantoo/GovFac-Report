<?php

namespace App\Modules\Report\Providers;

use App\Modules\Report\Contracts\ReportStatusService;
use App\Modules\Report\Policies\ReportPolicy;
use App\Modules\Report\Models\Report;
use App\Modules\Report\Services\EloquentReportStatusService;
use App\Modules\Shared\Providers\ModuleServiceProvider;
use Illuminate\Support\Facades\Gate;

class ReportServiceProvider extends ModuleServiceProvider
{
    /**
     * Daftarkan binding Contracts => implementasi milik modul ini.
     *
     * @var array<class-string, class-string>
     */
    protected array $contracts = [
        ReportStatusService::class => EloquentReportStatusService::class,
    ];

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Report::class, ReportPolicy::class);
    }
}
