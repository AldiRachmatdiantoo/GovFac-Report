<?php

namespace App\Modules\Notification\Tests\Feature;

use App\Modules\AiValidation\Jobs\ValidateReport;
use App\Modules\Auth\Models\User;
use App\Modules\Notification\Listeners\NotifyAdminsOfReportAwaitingReview;
use App\Modules\Report\Contracts\ReportStatusService;
use App\Modules\Report\Events\ReportStatusChanged;
use App\Modules\Report\Models\Report;
use App\Modules\Shared\Enums\ReportStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AdminReportNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_listener_is_registered(): void
    {
        Event::fake();

        Event::assertListening(ReportStatusChanged::class, NotifyAdminsOfReportAwaitingReview::class);
    }

    public function test_every_admin_is_notified_once_a_report_passes_ai_validation(): void
    {
        [$firstAdmin, $secondAdmin] = User::factory()->admin()->count(2)->create();
        $government = User::factory()->government()->create();
        $report = Report::factory()->create(['description' => 'Tiga lampu di depan taman mati sejak dua minggu.']);

        ValidateReport::dispatchSync($report, 1);

        $this->assertSame(ReportStatus::AdminReview, $report->fresh()->status);

        foreach ([$firstAdmin, $secondAdmin] as $admin) {
            $notification = $admin->notifications()->sole();
            $this->assertSame('report_awaiting_review', $notification->type);
            $this->assertSame($report->id, $notification->data['report_id']);
            $this->assertSame($report->code, $notification->data['report_code']);
        }

        $this->assertSame(0, $government->notifications()->count());
        $this->assertSame(0, $report->reporter->notifications()->count());
    }

    public function test_a_failed_ai_validation_does_not_reach_the_admins(): void
    {
        $admin = User::factory()->admin()->create();
        $report = Report::factory()->create(['description' => 'Rusak.']);

        ValidateReport::dispatchSync($report, 1);

        $this->assertSame(ReportStatus::Rejected, $report->fresh()->status);
        $this->assertSame(0, $admin->notifications()->count());
    }

    public function test_a_resubmitted_report_notifies_again_with_its_revision(): void
    {
        $admin = User::factory()->admin()->create();
        $report = Report::factory()->status(ReportStatus::AiValidation)->create(['revision' => 2]);

        app(ReportStatusService::class)->transition($report, ReportStatus::AdminReview);

        $this->assertSame(2, $admin->notifications()->sole()->data['revision']);
        $this->assertStringContainsString('revisi ke-2', $admin->notifications()->sole()->data['message']);
    }

    public function test_unread_count_and_mark_as_read_are_per_admin(): void
    {
        [$firstAdmin, $secondAdmin] = User::factory()->admin()->count(2)->create();

        foreach (Report::factory()->count(2)->create() as $report) {
            app(ReportStatusService::class)->transition($report, ReportStatus::AdminReview);
        }

        $this->actingAs($firstAdmin, 'api')->getJson('/api/v1/notifications/unread-count')->assertJsonPath('data.count', 2);

        $id = $firstAdmin->notifications()->first()->id;
        $this->actingAs($firstAdmin, 'api')->patchJson("/api/v1/notifications/{$id}/read")->assertOk();
        $this->actingAs($secondAdmin, 'api')->patchJson("/api/v1/notifications/{$id}/read")->assertNotFound();

        $this->actingAs($firstAdmin, 'api')->getJson('/api/v1/notifications/unread-count')->assertJsonPath('data.count', 1);
        $this->actingAs($secondAdmin, 'api')->getJson('/api/v1/notifications/unread-count')->assertJsonPath('data.count', 2);
    }
}
