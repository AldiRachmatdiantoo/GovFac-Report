<?php

namespace App\Modules\Report\Tests\Feature;

use App\Modules\Auth\Models\User;
use App\Modules\Report\Contracts\ReportStatusService;
use App\Modules\Report\Models\Report;
use App\Modules\Report\Models\ReportStatusHistory;
use App\Modules\Shared\Enums\ReasonCategory;
use App\Modules\Shared\Enums\ReportStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_request_returns_401(): void
    {
        $report = Report::factory()->create();

        $this->getJson("/api/v1/reports/{$report->id}/history")->assertUnauthorized();
    }

    public function test_history_is_ordered_oldest_first_and_includes_rejection_note(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $report = Report::factory()->create(['reporter_id' => $owner->id]);

        $service = app(ReportStatusService::class);
        $service->initialize($report, $owner);
        $service->transition($report, ReportStatus::AdminReview);
        $service->transition(
            $report,
            ReportStatus::Rejected,
            $admin,
            'Foto terlalu gelap, mohon unggah ulang.',
            ReasonCategory::UnclearEvidence,
        );

        $response = $this->actingAs($owner, 'api')
            ->getJson("/api/v1/reports/{$report->id}/history");

        $response->assertOk();
        $response->assertJsonCount(3, 'data');

        $this->assertSame(
            [[null, 'ai_validation'], ['ai_validation', 'admin_review'], ['admin_review', 'rejected']],
            collect($response->json('data'))->map(fn ($row) => [$row['from_status'], $row['to_status']])->all(),
        );

        $response->assertJsonPath('data.2.note', 'Foto terlalu gelap, mohon unggah ulang.');
        $response->assertJsonPath('data.2.reason_category', 'unclear_evidence');
        $response->assertJsonPath('data.2.actor.id', $admin->id);
        $response->assertJsonPath('data.2.actor.name', $admin->name);
        $response->assertJsonPath('data.2.actor.role', 'admin');
    }

    public function test_actor_is_null_for_system_entries(): void
    {
        $owner = User::factory()->create();
        $report = Report::factory()->create(['reporter_id' => $owner->id]);

        app(ReportStatusService::class)->initialize($report, null);

        $this->actingAs($owner, 'api')
            ->getJson("/api/v1/reports/{$report->id}/history")
            ->assertOk()
            ->assertJsonPath('data.0.actor', null)
            ->assertJsonPath('data.0.from_status', null)
            ->assertJsonPath('data.0.to_status', 'ai_validation');
    }

    public function test_other_user_gets_404(): void
    {
        $report = Report::factory()->create();
        $stranger = User::factory()->create();

        $this->actingAs($stranger, 'api')
            ->getJson("/api/v1/reports/{$report->id}/history")
            ->assertNotFound();
    }

    public function test_admin_can_view_any_history(): void
    {
        $report = Report::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'api')
            ->getJson("/api/v1/reports/{$report->id}/history")
            ->assertOk();
    }

    public function test_government_policy_applies_to_history(): void
    {
        $gov = User::factory()->government()->create();
        $hidden = Report::factory()->status(ReportStatus::AdminReview)->create();
        $visible = Report::factory()->status(ReportStatus::Forwarded)->create();

        $this->actingAs($gov, 'api')
            ->getJson("/api/v1/reports/{$hidden->id}/history")
            ->assertNotFound();

        $this->actingAs($gov, 'api')
            ->getJson("/api/v1/reports/{$visible->id}/history")
            ->assertOk();
    }

    public function test_missing_report_returns_404(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'api')
            ->getJson('/api/v1/reports/999999/history')
            ->assertNotFound();
    }
}
