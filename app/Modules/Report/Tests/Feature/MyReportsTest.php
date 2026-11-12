<?php

namespace App\Modules\Report\Tests\Feature;

use App\Modules\Auth\Models\User;
use App\Modules\Report\Models\Report;
use App\Modules\Report\Models\ReportPhoto;
use App\Modules\Report\Models\Tag;
use App\Modules\Shared\Enums\ReportStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/v1/reports/mine');

        $response->assertUnauthorized();
    }

    public function test_returns_only_authenticated_user_reports(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        Report::factory()->count(2)->create(['reporter_id' => $userA->id]);
        $reportB = Report::factory()->create(['reporter_id' => $userB->id]);

        $response = $this->actingAs($userA, 'api')
            ->getJson('/api/v1/reports/mine');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertNotContains($reportB->id, $ids);
    }

    public function test_ordered_by_newest_first(): void
    {
        $user = User::factory()->create();

        $older = Report::factory()->create([
            'reporter_id' => $user->id,
            'created_at' => now()->subDay(),
        ]);
        $newer = Report::factory()->create([
            'reporter_id' => $user->id,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/v1/reports/mine');

        $response->assertOk();
        $this->assertSame($newer->id, $response->json('data.0.id'));
        $this->assertSame($older->id, $response->json('data.1.id'));
    }

    public function test_filter_by_status_returns_only_matching_reports(): void
    {
        $user = User::factory()->create();

        $aiReport = Report::factory()->create([
            'reporter_id' => $user->id,
            'status' => ReportStatus::AiValidation,
        ]);
        $approvedReport = Report::factory()->create([
            'reporter_id' => $user->id,
            'status' => ReportStatus::Approved,
        ]);

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/v1/reports/mine?status=approved');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $this->assertSame($approvedReport->id, $response->json('data.0.id'));
    }

    public function test_unknown_status_returns_422(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/v1/reports/mine?status=status_ngawur');

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['status']);
    }

    public function test_eager_loads_photos_and_tags_properly(): void
    {
        $user = User::factory()->create();

        $report = Report::factory()->create(['reporter_id' => $user->id]);

        ReportPhoto::create([
            'report_id' => $report->id,
            'path' => 'photos/test.jpg',
            'mime' => 'image/jpeg',
            'size' => 1024,
            'sort_order' => 0,
        ]);

        $tag = Tag::create(['name' => 'jalan_rusak']);
        $report->tags()->attach($tag);

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/v1/reports/mine');

        $response->assertOk();
        $item = $response->json('data.0');

        $this->assertCount(1, $item['photos']);
        $this->assertSame('photos/test.jpg', $item['photos'][0]['path']);

        $this->assertCount(1, $item['tags']);
        $this->assertSame('jalan_rusak', $item['tags'][0]['name']);
    }
}
