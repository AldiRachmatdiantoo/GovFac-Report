<?php

namespace App\Modules\Analytics\Tests\Feature;

use App\Modules\Auth\Models\User;
use App\Modules\Report\Models\Report;
use App\Modules\Shared\Enums\ReportStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyReportStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_gets_401(): void
    {
        $this->getJson('/api/v1/me/report-stats')->assertUnauthorized();
    }

    public function test_admin_and_government_get_403(): void
    {
        foreach ([User::factory()->admin()->create(), User::factory()->government()->create()] as $user) {
            $this->actingAs($user, 'api')->getJson('/api/v1/me/report-stats')->assertForbidden();
        }
    }

    public function test_counts_match_the_users_own_reports_with_cancelled_kept_apart(): void
    {
        $user = User::factory()->create();

        $perStatus = [
            'ai_validation' => 1,
            'admin_review' => 2,
            'rejected' => 2,
            'approved' => 1,
            'forwarded' => 1,
            'gov_approved' => 1,
            'scheduled' => 1,
            'cancelled' => 3,
        ];

        foreach ($perStatus as $status => $count) {
            Report::factory()->count($count)->status(ReportStatus::from($status))->create(['reporter_id' => $user->id]);
        }

        Report::factory()->count(4)->status(ReportStatus::Approved)->create();

        $this->actingAs($user, 'api')
            ->getJson('/api/v1/me/report-stats')
            ->assertOk()
            ->assertExactJson(['data' => [
                'total' => 12,
                'in_process' => 3,
                'approved' => 4,
                'rejected' => 2,
                'cancelled' => 3,
                'by_status' => $perStatus,
            ]]);
    }

    public function test_a_user_without_reports_gets_zeros(): void
    {
        $this->actingAs(User::factory()->create(), 'api')
            ->getJson('/api/v1/me/report-stats')
            ->assertOk()
            ->assertJsonPath('data.total', 0)
            ->assertJsonPath('data.in_process', 0)
            ->assertJsonPath('data.approved', 0)
            ->assertJsonPath('data.rejected', 0)
            ->assertJsonPath('data.cancelled', 0)
            ->assertJsonPath('data.by_status', array_fill_keys(ReportStatus::values(), 0));
    }
}
