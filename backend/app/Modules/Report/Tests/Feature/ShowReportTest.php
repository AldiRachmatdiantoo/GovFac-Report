<?php

namespace App\Modules\Report\Tests\Feature;

use App\Modules\Auth\Models\User;
use App\Modules\Report\Models\Report;
use App\Modules\Shared\Enums\ReportStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ShowReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_request_returns_401(): void
    {
        $report = Report::factory()->create();

        $this->getJson("/api/v1/reports/{$report->id}")->assertUnauthorized();
    }

    public function test_owner_can_view_own_report(): void
    {
        $owner = User::factory()->create();
        $report = Report::factory()->create(['reporter_id' => $owner->id]);

        $response = $this->actingAs($owner, 'api')->getJson("/api/v1/reports/{$report->id}");

        $response->assertOk();
        $response->assertJsonPath('data.id', $report->id);
        $response->assertJsonPath('data.status', 'ai_validation');
        $response->assertJsonPath('data.reporter.id', $owner->id);
    }

    public function test_other_user_gets_404(): void
    {
        $report = Report::factory()->create();
        $stranger = User::factory()->create();

        $this->actingAs($stranger, 'api')
            ->getJson("/api/v1/reports/{$report->id}")
            ->assertNotFound();
    }

    public function test_admin_can_view_any_report(): void
    {
        $report = Report::factory()->create(['status' => ReportStatus::AiValidation]);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'api')
            ->getJson("/api/v1/reports/{$report->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $report->id);
    }

    /**
     * @return array<string, array{0: ReportStatus}>
     */
    public static function governmentVisibleStatuses(): array
    {
        return [
            'forwarded' => [ReportStatus::Forwarded],
            'gov_approved' => [ReportStatus::GovApproved],
            'scheduled' => [ReportStatus::Scheduled],
        ];
    }

    /**
     * @return array<string, array{0: ReportStatus}>
     */
    public static function governmentHiddenStatuses(): array
    {
        return [
            'ai_validation' => [ReportStatus::AiValidation],
            'admin_review' => [ReportStatus::AdminReview],
            'rejected' => [ReportStatus::Rejected],
            'approved' => [ReportStatus::Approved],
            'cancelled' => [ReportStatus::Cancelled],
        ];
    }

    #[DataProvider('governmentVisibleStatuses')]
    public function test_government_can_view_forwarded_and_later(ReportStatus $status): void
    {
        $report = Report::factory()->status($status)->create();
        $gov = User::factory()->government()->create();

        $this->actingAs($gov, 'api')
            ->getJson("/api/v1/reports/{$report->id}")
            ->assertOk();
    }

    #[DataProvider('governmentHiddenStatuses')]
    public function test_government_gets_404_for_earlier_statuses(ReportStatus $status): void
    {
        $report = Report::factory()->status($status)->create();
        $gov = User::factory()->government()->create();

        $this->actingAs($gov, 'api')
            ->getJson("/api/v1/reports/{$report->id}")
            ->assertNotFound();
    }

    public function test_missing_report_returns_404(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'api')
            ->getJson('/api/v1/reports/999999')
            ->assertNotFound();
    }

    public function test_non_numeric_id_is_not_routed_to_show(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'api')
            ->getJson('/api/v1/reports/abc')
            ->assertNotFound();
    }
}
