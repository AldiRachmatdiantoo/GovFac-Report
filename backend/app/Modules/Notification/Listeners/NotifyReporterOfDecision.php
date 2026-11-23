<?php

namespace App\Modules\Notification\Listeners;

use App\Modules\Notification\Services\NotificationService;
use App\Modules\Report\Events\ReportStatusChanged;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Queued, so a failing notification never rolls back or slows down the decision.
 */
class NotifyReporterOfDecision implements ShouldQueue
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function handle(ReportStatusChanged $event): void
    {
        $this->notifications->notifyReportDecision($event);
    }
}
