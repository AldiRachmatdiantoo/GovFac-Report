<?php

namespace App\Modules\Report\Tests\Feature;

use App\Modules\Auth\Models\User;
use App\Modules\Report\Contracts\ReportStatusService;
use App\Modules\Report\Events\ReportStatusChanged;
use App\Modules\Report\Models\Report;
use App\Modules\Report\Models\ReportStatusHistory;
use App\Modules\Shared\Enums\ReasonCategory;
use App\Modules\Shared\Enums\ReportStatus;
use App\Modules\Shared\Exceptions\InvalidStatusTransition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ReportStatusServiceTest extends TestCase
{
    use RefreshDatabase;

    private ReportStatusService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ReportStatusService::class);
    }

    public function test_initialize_records_initial_history(): void
    {
        $report = Report::factory()->create(['status' => ReportStatus::AiValidation]);
        $user = User::factory()->create();

        $this->service->initialize($report, $user);

        $history = ReportStatusHistory::where('report_id', $report->id)->first();
        $this->assertNotNull($history);
        $this->assertNull($history->from_status);
        $this->assertSame(ReportStatus::AiValidation, $history->to_status);
        $this->assertSame($user->id, $history->actor_id);
    }

    public function test_initialize_records_system_actor_when_null(): void
    {
        $report = Report::factory()->create(['status' => ReportStatus::AiValidation]);

        $this->service->initialize($report, null);

        $history = ReportStatusHistory::where('report_id', $report->id)->first();
        $this->assertNotNull($history);
        $this->assertNull($history->from_status);
        $this->assertSame(ReportStatus::AiValidation, $history->to_status);
        $this->assertNull($history->actor_id);
    }

    public function test_valid_transition_updates_status_and_appends_single_history(): void
    {
        Event::fake([ReportStatusChanged::class]);

        $report = Report::factory()->create(['status' => ReportStatus::AiValidation]);
        $admin = User::factory()->admin()->create();

        $updated = $this->service->transition(
            report: $report,
            to: ReportStatus::AdminReview,
            actor: $admin,
            note: 'Lolos validasi AI, lanjut ke admin review',
        );

        $this->assertSame(ReportStatus::AdminReview, $updated->status);
        $this->assertSame(ReportStatus::AdminReview, $report->fresh()->status);

        $histories = ReportStatusHistory::where('report_id', $report->id)->get();
        $this->assertCount(1, $histories);

        $history = $histories->first();
        $this->assertSame(ReportStatus::AiValidation, $history->from_status);
        $this->assertSame(ReportStatus::AdminReview, $history->to_status);
        $this->assertSame($admin->id, $history->actor_id);
        $this->assertSame('Lolos validasi AI, lanjut ke admin review', $history->note);
        $this->assertNull($history->reason_category);

        Event::assertDispatched(ReportStatusChanged::class, function (ReportStatusChanged $event) use ($report, $admin) {
            return $event->report->id === $report->id
                && $event->fromStatus === ReportStatus::AiValidation
                && $event->toStatus === ReportStatus::AdminReview
                && $event->actor?->id === $admin->id;
        });
    }

    public function test_rejection_transition_stores_reason_category(): void
    {
        $report = Report::factory()->create(['status' => ReportStatus::AdminReview]);
        $admin = User::factory()->admin()->create();

        $this->service->transition(
            report: $report,
            to: ReportStatus::Rejected,
            actor: $admin,
            note: 'Foto tidak jelas dan tidak ada lokasi',
            reasonCategory: ReasonCategory::UnclearEvidence,
        );

        $this->assertSame(ReportStatus::Rejected, $report->fresh()->status);

        $history = ReportStatusHistory::where('report_id', $report->id)->latest('id')->first();
        $this->assertSame(ReasonCategory::UnclearEvidence, $history->reason_category);
        $this->assertSame(ReportStatus::Rejected, $history->to_status);
    }

    public function test_invalid_transition_throws_exception_and_does_not_mutate(): void
    {
        Event::fake([ReportStatusChanged::class]);

        $report = Report::factory()->create(['status' => ReportStatus::AiValidation]);

        try {
            // ai_validation tidak boleh langsung ke approved
            $this->service->transition($report, ReportStatus::Approved);
            $this->fail('InvalidStatusTransition seharusnya dilempar.');
        } catch (InvalidStatusTransition $e) {
            $this->assertSame(ReportStatus::AiValidation, $e->current);
            $this->assertSame(ReportStatus::Approved, $e->target);
        }

        $this->assertSame(ReportStatus::AiValidation, $report->fresh()->status);
        $this->assertDatabaseCount('report_status_histories', 0);
        Event::assertNotDispatched(ReportStatusChanged::class);
    }

    public function test_transition_uses_locked_row_status_not_stale_instance(): void
    {
        $report = Report::factory()->create(['status' => ReportStatus::AiValidation]);

        // Simulasi proses lain yang sudah memindahkan status di database,
        // sementara instance $report milik pemanggil masih basi.
        Report::query()->whereKey($report->id)->update(['status' => ReportStatus::Cancelled->value]);

        $this->expectException(InvalidStatusTransition::class);

        $this->service->transition($report, ReportStatus::AdminReview);
    }

    public function test_transition_syncs_callers_instance(): void
    {
        $report = Report::factory()->create(['status' => ReportStatus::AiValidation]);

        $this->service->transition($report, ReportStatus::AdminReview);

        $this->assertSame(ReportStatus::AdminReview, $report->status);
    }
}
