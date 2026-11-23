<?php

namespace App\Modules\Community\Tests\Feature;

use App\Modules\Auth\Models\User;
use App\Modules\Report\Models\Report;
use App\Modules\Report\Models\Tag;
use App\Modules\Shared\Enums\Criticality;
use App\Modules\Shared\Enums\ReportStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $viewer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->viewer = User::factory()->create();
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<string>  $tags
     */
    private function report(ReportStatus $status = ReportStatus::AdminReview, array $attributes = [], array $tags = []): Report
    {
        $report = Report::factory()->status($status)->create($attributes);

        foreach ($tags as $name) {
            $report->tags()->attach(Tag::query()->firstOrCreate(['name' => $name]));
        }

        return $report;
    }

    /**
     * @return list<int>
     */
    private function feedIds(string $query): array
    {
        return $this->actingAs($this->viewer, 'api')
            ->getJson('/api/v1/feed?'.$query)
            ->assertOk()
            ->json('data.*.id');
    }

    public function test_search_matches_title_description_and_code_case_insensitively(): void
    {
        $byTitle = $this->report(attributes: ['title' => 'Lampu Jalan mati', 'description' => 'gelap']);
        $byDescription = $this->report(attributes: ['title' => 'Jalan berlubang', 'description' => 'Dekat LAMPU merah']);
        $byCode = $this->report(attributes: ['code' => 'GFR-2026-777001', 'title' => 'Trotoar', 'description' => 'rusak']);
        $this->report(attributes: ['title' => 'Taman', 'description' => 'kotor']);

        $this->assertEqualsCanonicalizing([$byTitle->id, $byDescription->id], $this->feedIds('q=lampu'));
        $this->assertSame([$byCode->id], $this->feedIds('q=777001'));
    }

    public function test_search_treats_percent_signs_literally(): void
    {
        $match = $this->report(attributes: ['title' => 'Diskon 50% lampu']);
        $this->report(attributes: ['title' => 'Lampu 500 watt']);

        $this->assertSame([$match->id], $this->feedIds('q='.urlencode('50%')));
    }

    public function test_status_filter_only_accepts_statuses_shown_in_the_feed(): void
    {
        $approved = $this->report(ReportStatus::Approved);
        $this->report(ReportStatus::AdminReview);

        $this->assertSame([$approved->id], $this->feedIds('status=approved'));

        $this->actingAs($this->viewer, 'api')
            ->getJson('/api/v1/feed?status=rejected')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }

    public function test_criticality_filter(): void
    {
        $high = $this->report(attributes: ['criticality' => Criticality::High]);
        $this->report(attributes: ['criticality' => Criticality::Low]);
        $this->report(attributes: ['criticality' => null]);

        $this->assertSame([$high->id], $this->feedIds('criticality=high'));
    }

    public function test_tag_filter_ignores_case_and_a_leading_hash(): void
    {
        $tagged = $this->report(tags: ['lampujalan', 'penerangan']);
        $this->report(tags: ['taman']);

        $this->assertSame([$tagged->id], $this->feedIds('tag=lampujalan'));
        $this->assertSame([$tagged->id], $this->feedIds('tag='.urlencode('#LampuJalan')));
        $this->assertSame([], $this->feedIds('tag=tidakada'));
    }

    public function test_filters_combine(): void
    {
        $match = $this->report(ReportStatus::Forwarded, ['title' => 'Lampu mati', 'criticality' => Criticality::High], ['lampujalan']);
        $this->report(ReportStatus::AdminReview, ['title' => 'Lampu mati', 'criticality' => Criticality::High], ['lampujalan']);
        $this->report(ReportStatus::Forwarded, ['title' => 'Lampu mati', 'criticality' => Criticality::Low], ['lampujalan']);
        $this->report(ReportStatus::Forwarded, ['title' => 'Lampu mati', 'criticality' => Criticality::High], ['taman']);
        $this->report(ReportStatus::Forwarded, ['title' => 'Jalan rusak', 'criticality' => Criticality::High], ['lampujalan']);

        $this->assertSame([$match->id], $this->feedIds('q=lampu&status=forwarded&criticality=high&tag=lampujalan'));
    }

    public function test_filters_never_reveal_reports_outside_the_feed(): void
    {
        $this->report(ReportStatus::Rejected, ['title' => 'Lampu mati'], ['lampujalan']);
        $this->report(ReportStatus::Cancelled, ['title' => 'Lampu mati'], ['lampujalan']);

        $this->assertSame([], $this->feedIds('q=lampu&tag=lampujalan'));
    }

    public function test_sort_by_likes_puts_the_most_liked_first_then_the_newest(): void
    {
        $old = $this->report(attributes: ['likes_count' => 5, 'created_at' => now()->subDays(2)]);
        $newTie = $this->report(attributes: ['likes_count' => 5, 'created_at' => now()->subDay()]);
        $newest = $this->report(attributes: ['likes_count' => 1]);

        $this->assertSame([$newTie->id, $old->id, $newest->id], $this->feedIds('sort=likes'));
        $this->assertSame([$newest->id, $newTie->id, $old->id], $this->feedIds('sort=newest'));
        $this->assertSame([$newest->id, $newTie->id, $old->id], $this->feedIds(''));
    }

    public function test_results_are_paginated_twenty_per_page_by_default(): void
    {
        Report::factory()->count(21)->status(ReportStatus::AdminReview)->create();

        $this->actingAs($this->viewer, 'api')
            ->getJson('/api/v1/feed?sort=likes')
            ->assertOk()
            ->assertJsonCount(20, 'data')
            ->assertJsonPath('meta.total', 21)
            ->assertJsonPath('meta.last_page', 2);

        $this->actingAs($this->viewer, 'api')
            ->getJson('/api/v1/feed?page=2')
            ->assertJsonCount(1, 'data');
    }

    public function test_invalid_filters_are_422(): void
    {
        $this->actingAs($this->viewer, 'api')
            ->getJson('/api/v1/feed?sort=oldest&criticality=extreme&q='.str_repeat('a', 101))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sort', 'criticality', 'q']);
    }
}
