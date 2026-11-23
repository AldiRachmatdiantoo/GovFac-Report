<?php

namespace App\Modules\Notification\Tests\Feature;

use App\Modules\Notification\Listeners\NotifyReporterOfDecision;
use App\Modules\Report\Contracts\ReportStatusService;
use App\Modules\Report\Events\ReportStatusChanged;
use App\Modules\Report\Models\Report;
use App\Modules\Shared\Enums\ReportStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ReportDecisionNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_listener_is_registered(): void
    {
        Event::fake();

        Event::assertListening(ReportStatusChanged::class, NotifyReporterOfDecision::class);
    }

    public function test_an_ai_rejection_tells_the_reporter_it_came_from_the_ai(): void
    {
        $report = Report::factory()->create();

        app(ReportStatusService::class)->transition($report, ReportStatus::Rejected, note: 'Deskripsi terlalu singkat.');

        $notification = $report->reporter->notifications()->sole();
        $this->assertSame('report_rejected', $notification->type);
        $this->assertStringContainsString('validasi AI', $notification->data['message']);
        $this->assertSame('Deskripsi terlalu singkat.', $notification->data['note']);
    }

    public function test_other_status_changes_do_not_notify_the_reporter(): void
    {
        $report = Report::factory()->create();

        app(ReportStatusService::class)->transition($report, ReportStatus::AdminReview);
        app(ReportStatusService::class)->transition($report, ReportStatus::Cancelled, $report->reporter);

        $this->assertSame(0, $report->reporter->notifications()->count());
    }
}
