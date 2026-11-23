<?php

namespace App\Modules\Verification\Tests\Feature;

use App\Modules\Auth\Models\User;
use App\Modules\Report\Contracts\ReportStatusService;
use App\Modules\Report\Models\Report;
use App\Modules\Report\Models\ReportStatusHistory;
use App\Modules\Shared\Enums\ReasonCategory;
use App\Modules\Shared\Enums\ReportStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AdminQueueAndHistoryTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    /**
     * A report that went through the AI and entered admin_review at $enteredAt.
     */
    private function awaitingReview(Carbon $enteredAt, int $likes = 0): Report
    {
        $report = Report::factory()->create(['likes_count' => $likes]);

        Carbon::setTestNow($enteredAt);
        app(ReportStatusService::class)->transition($report, ReportStatus::AdminReview);
        Carbon::setTestNow();

        return $report;
    }

    private function decision(Report $report, ReportStatus $decision, Carbon $at, ?ReasonCategory $category = null): void
    {
        Carbon::setTestNow($at);
        app(ReportStatusService::class)->transition($report, $decision, $this->admin, 'Catatan admin cukup panjang.', $category);
        Carbon::setTestNow();
    }

    public function test_queue_lists_admin_review_only_most_liked_first_then_longest_waiting(): void
    {
        $newPopular = $this->awaitingReview(now()->subHour(), likes: 10);
        $oldQuiet = $this->awaitingReview(now()->subDays(3), likes: 2);
        $newQuiet = $this->awaitingReview(now()->subDay(), likes: 2);

        foreach ([ReportStatus::AiValidation, ReportStatus::Approved, ReportStatus::Rejected, ReportStatus::Cancelled] as $status) {
            Report::factory()->status($status)->create(['likes_count' => 99]);
        }

        $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/reports/queue')
            ->assertOk()
            ->assertJsonPath('data.*.id', [$newPopular->id, $oldQuiet->id, $newQuiet->id])
            ->assertJsonPath('data.1.waiting_since', now()->subDays(3)->startOfSecond()->toISOString())
            ->assertJsonPath('meta.total', 3);
    }

    public function test_waiting_time_counts_from_the_latest_entry_into_admin_review(): void
    {
        $resubmitted = $this->awaitingReview(now()->subDays(5));
        $this->decision($resubmitted, ReportStatus::Rejected, now()->subDays(4), ReasonCategory::Incomplete);
        app(ReportStatusService::class)->transition($resubmitted, ReportStatus::AiValidation, $resubmitted->reporter);
        Carbon::setTestNow(now()->subHour());
        app(ReportStatusService::class)->transition($resubmitted, ReportStatus::AdminReview);
        Carbon::setTestNow();

        $waitingLonger = $this->awaitingReview(now()->subDays(2));

        $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/reports/queue')
            ->assertJsonPath('data.*.id', [$waitingLonger->id, $resubmitted->id]);
    }

    public function test_a_decision_moves_the_report_from_the_queue_to_the_history_without_a_manual_step(): void
    {
        $report = $this->awaitingReview(now()->subDay());

        $this->actingAs($this->admin, 'api')->getJson('/api/v1/admin/reports/queue')->assertJsonPath('data.*.id', [$report->id]);
        $this->actingAs($this->admin, 'api')->getJson('/api/v1/admin/reports/history')->assertJsonCount(0, 'data');

        $this->actingAs($this->admin, 'api')->postJson("/api/v1/admin/reports/{$report->id}/approve")->assertOk();

        $this->actingAs($this->admin, 'api')->getJson('/api/v1/admin/reports/queue')->assertJsonCount(0, 'data');
        $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/reports/history')
            ->assertJsonPath('data.0.decision', 'approved')
            ->assertJsonPath('data.0.decided_by.id', $this->admin->id)
            ->assertJsonPath('data.0.report.id', $report->id)
            ->assertJsonPath('data.0.report.status', 'approved');
    }

    public function test_history_holds_admin_decisions_only_newest_first(): void
    {
        $approved = $this->awaitingReview(now()->subDays(3));
        $this->decision($approved, ReportStatus::Approved, now()->subDays(2));

        $rejected = $this->awaitingReview(now()->subDays(3));
        $this->decision($rejected, ReportStatus::Rejected, now()->subDay(), ReasonCategory::Duplicate);

        // Rejected by the AI, never seen by an admin.
        app(ReportStatusService::class)->transition(Report::factory()->create(), ReportStatus::Rejected, note: 'AI');

        $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/reports/history')
            ->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.0.report.id', $rejected->id)
            ->assertJsonPath('data.0.decision', 'rejected')
            ->assertJsonPath('data.0.reason_category', 'duplicate')
            ->assertJsonPath('data.0.note', 'Catatan admin cukup panjang.')
            ->assertJsonPath('data.1.report.id', $approved->id);
    }

    public function test_history_filters_by_decision_and_inclusive_date_range(): void
    {
        $early = $this->awaitingReview(Carbon::parse('2026-11-01 08:00:00'));
        $this->decision($early, ReportStatus::Approved, Carbon::parse('2026-11-02 23:30:00'));

        $middle = $this->awaitingReview(Carbon::parse('2026-11-01 08:00:00'));
        $this->decision($middle, ReportStatus::Rejected, Carbon::parse('2026-11-05 10:00:00'), ReasonCategory::Other);

        $late = $this->awaitingReview(Carbon::parse('2026-11-01 08:00:00'));
        $this->decision($late, ReportStatus::Approved, Carbon::parse('2026-11-09 00:00:00'));

        $ids = fn (string $query): array => $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/reports/history?'.$query)
            ->assertOk()
            ->json('data.*.report.id');

        $this->assertSame([$late->id, $early->id], $ids('decision=approved'));
        $this->assertSame([$middle->id], $ids('decision=rejected'));
        $this->assertSame([$middle->id, $early->id], $ids('from=2026-11-02&to=2026-11-05'));
        $this->assertSame([$late->id], $ids('decision=approved&from=2026-11-03'));
    }

    public function test_history_input_is_validated(): void
    {
        $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/reports/history?decision=cancelled&from=02-11-2026')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['decision', 'from']);

        $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/reports/history?from=2026-11-05&to=2026-11-01')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('to');
    }

    public function test_only_admins_reach_the_queue_and_the_history(): void
    {
        $urls = ['/api/v1/admin/reports/queue', '/api/v1/admin/reports/history'];

        foreach ($urls as $url) {
            $this->getJson($url)->assertUnauthorized();
        }

        foreach ($urls as $url) {
            foreach ([User::factory()->create(), User::factory()->government()->create()] as $user) {
                $this->actingAs($user, 'api')->getJson($url)->assertForbidden();
            }
        }
    }

    public function test_history_rows_stay_append_only(): void
    {
        $report = $this->awaitingReview(now()->subDay());
        $this->decision($report, ReportStatus::Approved, now());

        $this->assertSame(2, ReportStatusHistory::query()->where('report_id', $report->id)->count());
    }
}
