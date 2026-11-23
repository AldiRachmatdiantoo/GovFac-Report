<?php

namespace App\Modules\Verification\Tests\Feature;

use App\Modules\AiValidation\Contracts\AiVerdict;
use App\Modules\AiValidation\Models\AiValidation;
use App\Modules\Auth\Models\User;
use App\Modules\Report\Models\Report;
use App\Modules\Shared\Enums\Criticality;
use App\Modules\Shared\Enums\ReportStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReportDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_the_report_with_photos_likes_and_the_latest_ai_result(): void
    {
        $report = Report::factory()->status(ReportStatus::AdminReview)->create(['revision' => 2, 'likes_count' => 7]);
        $report->photos()->create(['path' => 'reports/a.jpg', 'mime' => 'image/jpeg', 'size' => 10, 'sort_order' => 0]);
        $report->photos()->create(['path' => 'reports/b.jpg', 'mime' => 'image/jpeg', 'size' => 10, 'sort_order' => 1]);

        AiValidation::factory()->verdict(AiVerdict::Fail)->create(['report_id' => $report->id, 'revision' => 1, 'reasons' => ['Lama']]);
        AiValidation::factory()->verdict(AiVerdict::Flag)->create([
            'report_id' => $report->id,
            'revision' => 2,
            'score' => 0.42,
            'criticality' => Criticality::High,
            'reasons' => ['Foto kurang jelas.'],
        ]);

        $this->actingAs(User::factory()->admin()->create(), 'api')
            ->getJson("/api/v1/admin/reports/{$report->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $report->id)
            ->assertJsonPath('data.description', $report->description)
            ->assertJsonPath('data.likes_count', 7)
            ->assertJsonCount(2, 'data.photos')
            ->assertJsonPath('data.ai_validation.revision', 2)
            ->assertJsonPath('data.ai_validation.verdict', 'flag')
            ->assertJsonPath('data.ai_validation.score', 0.42)
            ->assertJsonPath('data.ai_validation.criticality', 'high')
            ->assertJsonPath('data.ai_validation.reasons', ['Foto kurang jelas.']);
    }

    public function test_ai_validation_is_null_while_the_ai_has_not_finished(): void
    {
        $report = Report::factory()->create();

        $this->actingAs(User::factory()->admin()->create(), 'api')
            ->getJson("/api/v1/admin/reports/{$report->id}")
            ->assertOk()
            ->assertJsonPath('data.ai_validation', null);
    }

    public function test_access_rules(): void
    {
        $report = Report::factory()->status(ReportStatus::AdminReview)->create();

        $this->getJson("/api/v1/admin/reports/{$report->id}")->assertUnauthorized();

        foreach ([User::factory()->create(), User::factory()->government()->create(), $report->reporter] as $user) {
            $this->actingAs($user, 'api')->getJson("/api/v1/admin/reports/{$report->id}")->assertForbidden();
        }

        $this->actingAs(User::factory()->admin()->create(), 'api')
            ->getJson('/api/v1/admin/reports/999999')
            ->assertNotFound();
    }
}
