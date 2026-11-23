<?php

namespace App\Modules\Report\Tests\Feature;

use App\Modules\Auth\Models\User;
use App\Modules\Report\Models\Report;
use App\Modules\Report\Models\ReportStatusHistory;
use App\Modules\Shared\Enums\ReportStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CancelReportTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
    }

    private function report(ReportStatus $status): Report
    {
        return Report::factory()->status($status)->create(['reporter_id' => $this->owner->id]);
    }

    /**
     * @return array<string, array{ReportStatus}>
     */
    public static function cancellable(): array
    {
        return [
            'ai_validation' => [ReportStatus::AiValidation],
            'admin_review' => [ReportStatus::AdminReview],
            'approved' => [ReportStatus::Approved],
            'forwarded' => [ReportStatus::Forwarded],
        ];
    }

    #[DataProvider('cancellable')]
    public function test_owner_cancels_a_report_the_government_has_not_approved_yet(ReportStatus $status): void
    {
        $report = $this->report($status);

        $this->actingAs($this->owner, 'api')
            ->postJson("/api/v1/reports/{$report->id}/cancel", ['reason' => '  Lampunya sudah diperbaiki warga.  '])
            ->assertOk()
            ->assertJsonPath('data.id', $report->id)
            ->assertJsonPath('data.status', 'cancelled');

        $history = ReportStatusHistory::query()->where('report_id', $report->id)->sole();
        $this->assertSame($status, $history->from_status);
        $this->assertSame(ReportStatus::Cancelled, $history->to_status);
        $this->assertSame($this->owner->id, $history->actor_id);
        $this->assertSame('Lampunya sudah diperbaiki warga.', $history->note);
    }

    public function test_the_reason_is_optional(): void
    {
        $report = $this->report(ReportStatus::AdminReview);

        $this->actingAs($this->owner, 'api')
            ->postJson("/api/v1/reports/{$report->id}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertNull(ReportStatusHistory::query()->where('report_id', $report->id)->sole()->note);
    }

    /**
     * @return array<string, array{ReportStatus}>
     */
    public static function notCancellable(): array
    {
        return [
            'rejected' => [ReportStatus::Rejected],
            'gov_approved' => [ReportStatus::GovApproved],
            'scheduled' => [ReportStatus::Scheduled],
            'cancelled' => [ReportStatus::Cancelled],
        ];
    }

    #[DataProvider('notCancellable')]
    public function test_cancelling_from_gov_approved_on_or_a_final_status_is_409(ReportStatus $status): void
    {
        $report = $this->report($status);

        $this->actingAs($this->owner, 'api')
            ->postJson("/api/v1/reports/{$report->id}/cancel")
            ->assertConflict()
            ->assertExactJson([
                'message' => "Laporan berstatus {$status->value} tidak bisa dibatalkan.",
                'code' => 'invalid_status_transition',
                'current_status' => $status->value,
            ]);

        $this->assertSame($status, $report->fresh()->status);
        $this->assertDatabaseCount('report_status_histories', 0);
    }

    public function test_only_the_owner_may_cancel(): void
    {
        $report = $this->report(ReportStatus::AdminReview);

        $this->postJson("/api/v1/reports/{$report->id}/cancel")->assertUnauthorized();

        // A report in the community feed is visible to other users, so they get 403 rather than 404.
        $this->actingAs(User::factory()->create(), 'api')
            ->postJson("/api/v1/reports/{$report->id}/cancel")->assertForbidden();

        $hidden = $this->report(ReportStatus::AiValidation);
        $this->actingAs(User::factory()->create(), 'api')
            ->postJson("/api/v1/reports/{$hidden->id}/cancel")->assertNotFound();
        $this->assertSame(ReportStatus::AiValidation, $hidden->fresh()->status);

        $this->actingAs(User::factory()->government()->create(), 'api')
            ->postJson("/api/v1/reports/{$report->id}/cancel")->assertForbidden();

        $this->actingAs($this->owner, 'api')
            ->postJson('/api/v1/reports/999999/cancel')->assertNotFound();

        $this->assertSame(ReportStatus::AdminReview, $report->fresh()->status);
    }

    public function test_the_reason_is_validated(): void
    {
        $report = $this->report(ReportStatus::AdminReview);

        $this->actingAs($this->owner, 'api')
            ->postJson("/api/v1/reports/{$report->id}/cancel", ['reason' => str_repeat('a', 501)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('reason');
    }
}
