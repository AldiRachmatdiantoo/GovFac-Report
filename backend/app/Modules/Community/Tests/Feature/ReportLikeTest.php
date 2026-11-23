<?php

namespace App\Modules\Community\Tests\Feature;

use App\Modules\Auth\Models\User;
use App\Modules\Community\Models\ReportLike;
use App\Modules\Report\Models\Report;
use App\Modules\Shared\Enums\ReportStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ReportLikeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Report $report;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->report = Report::factory()->status(ReportStatus::AdminReview)->create();
    }

    public function test_liking_twice_keeps_one_like_and_one_count(): void
    {
        foreach ([1, 2] as $attempt) {
            $this->actingAs($this->user, 'api')
                ->putJson("/api/v1/reports/{$this->report->id}/like")
                ->assertOk()
                ->assertExactJson(['data' => ['report_id' => $this->report->id, 'liked' => true, 'likes_count' => 1]]);
        }

        $this->assertSame(1, ReportLike::query()->count());
        $this->assertSame(1, $this->report->fresh()->likes_count);
    }

    public function test_a_like_does_not_change_the_reports_updated_at(): void
    {
        $this->report->forceFill(['updated_at' => now()->subDay()])->save();
        $before = $this->report->fresh()->updated_at;

        $this->actingAs($this->user, 'api')->putJson("/api/v1/reports/{$this->report->id}/like")->assertOk();
        $this->actingAs($this->user, 'api')->deleteJson("/api/v1/reports/{$this->report->id}/like")->assertOk();

        $this->assertEquals($before, $this->report->fresh()->updated_at);
    }

    public function test_likes_from_different_users_add_up(): void
    {
        $this->actingAs($this->user, 'api')->putJson("/api/v1/reports/{$this->report->id}/like")->assertOk();

        $this->actingAs(User::factory()->create(), 'api')
            ->putJson("/api/v1/reports/{$this->report->id}/like")
            ->assertJsonPath('data.likes_count', 2);
    }

    public function test_unliking_removes_the_like_and_repeating_it_changes_nothing(): void
    {
        $this->actingAs($this->user, 'api')->putJson("/api/v1/reports/{$this->report->id}/like");

        foreach ([1, 2] as $attempt) {
            $this->actingAs($this->user, 'api')
                ->deleteJson("/api/v1/reports/{$this->report->id}/like")
                ->assertOk()
                ->assertExactJson(['data' => ['report_id' => $this->report->id, 'liked' => false, 'likes_count' => 0]]);
        }

        $this->assertSame(0, ReportLike::query()->count());
        $this->assertSame(0, $this->report->fresh()->likes_count);
    }

    public function test_unliking_without_a_like_keeps_other_users_likes(): void
    {
        $this->actingAs(User::factory()->create(), 'api')->putJson("/api/v1/reports/{$this->report->id}/like");

        $this->actingAs($this->user, 'api')
            ->deleteJson("/api/v1/reports/{$this->report->id}/like")
            ->assertJsonPath('data.likes_count', 1);
    }

    /**
     * @return array<string, array{ReportStatus}>
     */
    public static function notInFeed(): array
    {
        return [
            'ai_validation' => [ReportStatus::AiValidation],
            'rejected' => [ReportStatus::Rejected],
            'cancelled' => [ReportStatus::Cancelled],
        ];
    }

    #[DataProvider('notInFeed')]
    public function test_reports_outside_the_feed_cannot_be_liked(ReportStatus $status): void
    {
        $report = Report::factory()->status($status)->create();

        $this->actingAs($this->user, 'api')->putJson("/api/v1/reports/{$report->id}/like")->assertNotFound();
        $this->actingAs($this->user, 'api')->deleteJson("/api/v1/reports/{$report->id}/like")->assertNotFound();

        $this->assertSame(0, ReportLike::query()->count());
    }

    public function test_missing_report_is_404(): void
    {
        $this->actingAs($this->user, 'api')->putJson('/api/v1/reports/999999/like')->assertNotFound();
    }

    public function test_guest_gets_401_and_admin_or_government_get_403(): void
    {
        $this->putJson("/api/v1/reports/{$this->report->id}/like")->assertUnauthorized();
        $this->deleteJson("/api/v1/reports/{$this->report->id}/like")->assertUnauthorized();

        foreach ([User::factory()->admin()->create(), User::factory()->government()->create()] as $user) {
            $this->actingAs($user, 'api')->putJson("/api/v1/reports/{$this->report->id}/like")->assertForbidden();
            $this->actingAs($user, 'api')->deleteJson("/api/v1/reports/{$this->report->id}/like")->assertForbidden();
        }

        $this->assertSame(0, $this->report->fresh()->likes_count);
    }
}
