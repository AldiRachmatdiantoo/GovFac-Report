<?php

namespace App\Modules\Community\Tests\Feature;

use App\Modules\Auth\Models\User;
use App\Modules\Community\Models\ReportLike;
use App\Modules\Report\Models\Report;
use App\Modules\Shared\Enums\ReportStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_gets_401(): void
    {
        $this->getJson('/api/v1/feed')->assertUnauthorized();
    }

    public function test_feed_shows_only_reports_that_passed_ai_validation_and_are_still_alive(): void
    {
        $visible = [];

        foreach (ReportStatus::cases() as $status) {
            $report = Report::factory()->status($status)->create();

            if (! in_array($status, [ReportStatus::AiValidation, ReportStatus::Rejected, ReportStatus::Cancelled], true)) {
                $visible[] = $report->id;
            }
        }

        $ids = $this->actingAs(User::factory()->create(), 'api')
            ->getJson('/api/v1/feed')
            ->assertOk()
            ->assertJsonPath('meta.total', 5)
            ->json('data.*.id');

        $this->assertEqualsCanonicalizing($visible, $ids);
    }

    public function test_every_role_can_read_the_feed(): void
    {
        Report::factory()->status(ReportStatus::AdminReview)->create();

        foreach ([User::factory()->create(), User::factory()->admin()->create(), User::factory()->government()->create()] as $user) {
            $this->actingAs($user, 'api')->getJson('/api/v1/feed')->assertOk()->assertJsonCount(1, 'data');
        }
    }

    public function test_feed_items_are_newest_first_and_hide_the_description_and_reporter_contact(): void
    {
        $older = Report::factory()->status(ReportStatus::Approved)->create(['created_at' => now()->subDay()]);
        $newer = Report::factory()->status(ReportStatus::AdminReview)->create(['likes_count' => 3]);
        $newer->photos()->create(['path' => 'reports/a.jpg', 'mime' => 'image/jpeg', 'size' => 10, 'sort_order' => 0]);
        $newer->photos()->create(['path' => 'reports/b.jpg', 'mime' => 'image/jpeg', 'size' => 10, 'sort_order' => 1]);

        $response = $this->actingAs(User::factory()->create(), 'api')
            ->getJson('/api/v1/feed')
            ->assertOk()
            ->assertJsonPath('data.0.id', $newer->id)
            ->assertJsonPath('data.1.id', $older->id)
            ->assertJsonPath('data.0.likes_count', 3)
            ->assertJsonPath('data.0.liked_by_me', false)
            ->assertJsonPath('data.0.reporter', ['id' => $newer->reporter->id, 'name' => $newer->reporter->name])
            ->assertJsonCount(1, 'data.0.photos')
            ->assertJsonPath('meta.per_page', 20);

        $this->assertArrayNotHasKey('description', $response->json('data.0'));
        $this->assertStringNotContainsString($newer->reporter->email, $response->getContent());
    }

    public function test_liked_by_me_reflects_the_viewer_only(): void
    {
        $viewer = User::factory()->create();
        $liked = Report::factory()->status(ReportStatus::AdminReview)->create();
        $likedBySomeoneElse = Report::factory()->status(ReportStatus::AdminReview)->create();

        ReportLike::query()->create(['report_id' => $liked->id, 'user_id' => $viewer->id]);
        ReportLike::query()->create(['report_id' => $likedBySomeoneElse->id, 'user_id' => User::factory()->create()->id]);

        $items = collect($this->actingAs($viewer, 'api')->getJson('/api/v1/feed')->assertOk()->json('data'))
            ->pluck('liked_by_me', 'id');

        $this->assertTrue($items[$liked->id]);
        $this->assertFalse($items[$likedBySomeoneElse->id]);
    }

    public function test_per_page_is_validated(): void
    {
        $this->actingAs(User::factory()->create(), 'api')
            ->getJson('/api/v1/feed?per_page=51')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('per_page');
    }

    public function test_another_user_may_open_a_feed_report_but_not_one_outside_the_feed(): void
    {
        $stranger = User::factory()->create();
        $inFeed = Report::factory()->status(ReportStatus::AdminReview)->create();
        $rejected = Report::factory()->status(ReportStatus::Rejected)->create();

        $this->actingAs($stranger, 'api')->getJson("/api/v1/reports/{$inFeed->id}")->assertOk();
        $this->actingAs($stranger, 'api')->getJson("/api/v1/reports/{$inFeed->id}/history")->assertOk();
        $this->actingAs($stranger, 'api')->getJson("/api/v1/reports/{$rejected->id}")->assertNotFound();
    }
}
