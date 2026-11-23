<?php

namespace App\Modules\Report\Providers;

use App\Modules\Report\Contracts\ReportCriticalityWriter;
use App\Modules\Report\Contracts\ReportFeed;
use App\Modules\Report\Contracts\ReportLikeCounter;
use App\Modules\Report\Contracts\ReportStatistics;
use App\Modules\Report\Contracts\ReportStatusService;
use App\Modules\Report\Models\Report;
use App\Modules\Report\Policies\ReportPolicy;
use App\Modules\Report\Services\EloquentReportCriticalityWriter;
use App\Modules\Report\Services\EloquentReportFeed;
use App\Modules\Report\Services\EloquentReportLikeCounter;
use App\Modules\Report\Services\EloquentReportStatistics;
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
        ReportStatistics::class => EloquentReportStatistics::class,
        ReportCriticalityWriter::class => EloquentReportCriticalityWriter::class,
        ReportFeed::class => EloquentReportFeed::class,
        ReportLikeCounter::class => EloquentReportLikeCounter::class,
    ];

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Report::class, ReportPolicy::class);
    }
}
