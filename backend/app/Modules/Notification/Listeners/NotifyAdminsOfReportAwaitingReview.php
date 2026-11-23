<?php

namespace App\Modules\Notification\Listeners;

use App\Modules\Notification\Services\NotificationService;
use App\Modules\Report\Events\ReportStatusChanged;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Queued, so notifying every admin never slows down the AI validation job.
 */
class NotifyAdminsOfReportAwaitingReview implements ShouldQueue
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function handle(ReportStatusChanged $event): void
    {
        $this->notifications->notifyAdminsOfReportAwaitingReview($event);
    }
}
