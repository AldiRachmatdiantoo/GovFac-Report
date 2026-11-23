<?php

namespace App\Modules\Notification\Providers;

use App\Modules\Auth\Events\UserLoggedIn;
use App\Modules\Notification\Listeners\NotifyAdminsOfReportAwaitingReview;
use App\Modules\Notification\Listeners\NotifyReporterOfDecision;
use App\Modules\Notification\Listeners\SendLoginNotification;
use App\Modules\Report\Events\ReportStatusChanged;
use App\Modules\Shared\Providers\ModuleServiceProvider;
use Illuminate\Support\Facades\Event;

class NotificationServiceProvider extends ModuleServiceProvider
{
    /**
     * Daftarkan binding Contracts => implementasi milik modul ini.
     *
     * @var array<class-string, class-string>
     */
    protected array $contracts = [];

    public function boot(): void
    {
        parent::boot();

        Event::listen(UserLoggedIn::class, SendLoginNotification::class);
        Event::listen(ReportStatusChanged::class, NotifyReporterOfDecision::class);
        Event::listen(ReportStatusChanged::class, NotifyAdminsOfReportAwaitingReview::class);
    }
}
