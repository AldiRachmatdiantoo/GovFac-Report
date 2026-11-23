<?php

namespace App\Modules\Verification\Tests\Feature;

use App\Modules\Auth\Models\User;
use App\Modules\Report\Models\Report;
use App\Modules\Report\Models\ReportStatusHistory;
use App\Modules\Shared\Enums\ReasonCategory;
use App\Modules\Shared\Enums\ReportStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminDecisionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Report $report;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->report = Report::factory()->status(ReportStatus::AdminReview)->create();
    }

    public function test_admin_approves_with_an_optional_note_and_the_reporter_is_notified(): void
    {
        $this->actingAs($this->admin, 'api')
            ->postJson("/api/v1/admin/reports/{$this->report->id}/approve", ['note' => 'Sudah dicek.'])
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $history = ReportStatusHistory::query()->where('report_id', $this->report->id)->sole();
        $this->assertSame(ReportStatus::AdminReview, $history->from_status);
        $this->assertSame(ReportStatus::Approved, $history->to_status);
        $this->assertSame($this->admin->id, $history->actor_id);
        $this->assertSame('Sudah dicek.', $history->note);

        /** @var DatabaseNotification $notification */
        $notification = $this->report->reporter->notifications()->sole();
        $this->assertSame('report_approved', $notification->type);
        $this->assertSame($this->report->id, $notification->data['report_id']);
        $this->assertSame('approved', $notification->data['status']);
    }

    public function test_approving_without_a_note_works(): void
    {
        $this->actingAs($this->admin, 'api')
            ->postJson("/api/v1/admin/reports/{$this->report->id}/approve")
            ->assertOk();

        $this->assertNull(ReportStatusHistory::query()->sole()->note);
    }

    public function test_admin_rejects_with_category_and_note_which_the_reporter_can_read(): void
    {
        $this->actingAs($this->admin, 'api')
            ->postJson("/api/v1/admin/reports/{$this->report->id}/reject", [
                'reason_category' => 'unclear_evidence',
                'note' => 'Foto terlalu gelap, mohon ulangi.',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'rejected');

        $history = ReportStatusHistory::query()->where('report_id', $this->report->id)->sole();
        $this->assertSame(ReportStatus::Rejected, $history->to_status);
        $this->assertSame(ReasonCategory::UnclearEvidence, $history->reason_category);

        $notification = $this->report->reporter->notifications()->sole();
        $this->assertSame('report_rejected', $notification->type);
        $this->assertSame('unclear_evidence', $notification->data['reason_category']);
        $this->assertSame('Foto terlalu gelap, mohon ulangi.', $notification->data['note']);

        $this->actingAs($this->report->reporter, 'api')
            ->getJson("/api/v1/reports/{$this->report->id}/history")
            ->assertJsonPath('data.0.note', 'Foto terlalu gelap, mohon ulangi.')
            ->assertJsonPath('data.0.reason_category', 'unclear_evidence');
    }

    public function test_reject_requires_a_known_category_and_a_note_of_at_least_ten_characters(): void
    {
        $this->actingAs($this->admin, 'api')
            ->postJson("/api/v1/admin/reports/{$this->report->id}/reject", ['reason_category' => 'boring', 'note' => 'pendek'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['reason_category', 'note']);

        $this->actingAs($this->admin, 'api')
            ->postJson("/api/v1/admin/reports/{$this->report->id}/reject")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['reason_category', 'note']);

        $this->assertSame(ReportStatus::AdminReview, $this->report->fresh()->status);
    }

    /**
     * @return array<string, array{ReportStatus}>
     */
    public static function notInReview(): array
    {
        return array_map(
            fn (ReportStatus $status): array => [$status],
            array_filter(array_combine(ReportStatus::values(), ReportStatus::cases()), fn (ReportStatus $status): bool => $status !== ReportStatus::AdminReview),
        );
    }

    #[DataProvider('notInReview')]
    public function test_decisions_outside_admin_review_are_409(ReportStatus $status): void
    {
        $report = Report::factory()->status($status)->create();

        $this->actingAs($this->admin, 'api')
            ->postJson("/api/v1/admin/reports/{$report->id}/approve")
            ->assertConflict()
            ->assertJsonPath('code', 'invalid_status_transition')
            ->assertJsonPath('current_status', $status->value);

        $this->actingAs($this->admin, 'api')
            ->postJson("/api/v1/admin/reports/{$report->id}/reject", ['reason_category' => 'other', 'note' => 'Catatan yang cukup panjang.'])
            ->assertConflict();

        $this->assertSame($status, $report->fresh()->status);
        $this->assertDatabaseCount('report_status_histories', 0);
    }

    public function test_deciding_twice_is_409(): void
    {
        $this->actingAs($this->admin, 'api')->postJson("/api/v1/admin/reports/{$this->report->id}/approve")->assertOk();
        $this->actingAs($this->admin, 'api')->postJson("/api/v1/admin/reports/{$this->report->id}/approve")->assertConflict();
    }

    public function test_access_rules(): void
    {
        $url = "/api/v1/admin/reports/{$this->report->id}/approve";

        $this->postJson($url)->assertUnauthorized();

        foreach ([User::factory()->create(), User::factory()->government()->create(), $this->report->reporter] as $user) {
            $this->actingAs($user, 'api')->postJson($url)->assertForbidden();
            $this->actingAs($user, 'api')
                ->postJson("/api/v1/admin/reports/{$this->report->id}/reject", ['reason_category' => 'other', 'note' => 'Catatan yang cukup panjang.'])
                ->assertForbidden();
        }

        $this->actingAs($this->admin, 'api')->postJson('/api/v1/admin/reports/999999/approve')->assertNotFound();
        $this->assertSame(ReportStatus::AdminReview, $this->report->fresh()->status);
    }
}
