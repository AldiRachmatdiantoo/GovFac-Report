<?php

namespace App\Modules\Report\Tests\Feature;

use App\Modules\Auth\Models\User;
use App\Modules\Report\Models\Report;
use App\Modules\Report\Models\ReportStatusHistory;
use App\Modules\Report\Models\Tag;
use App\Modules\Shared\Enums\ReasonCategory;
use App\Modules\Shared\Enums\ReportStatus;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_creates_report_with_defaults(): void
    {
        $report = Report::factory()->create()->fresh();

        $this->assertSame(ReportStatus::AiValidation, $report->status);
        $this->assertSame(1, $report->revision);
        $this->assertSame(0, $report->likes_count);
        $this->assertNull($report->criticality);
        $this->assertInstanceOf(User::class, $report->reporter);
    }

    public function test_status_cannot_be_mass_assigned(): void
    {
        $report = Report::create([
            'reporter_id' => User::factory()->create()->id,
            'title' => 'Lampu jalan mati',
            'description' => 'Tiga lampu mati.',
            'status' => 'approved',
        ]);

        $this->assertSame(ReportStatus::AiValidation, $report->fresh()->status);
    }

    public function test_relations_photos_tags_and_histories(): void
    {
        $report = Report::factory()->create();

        $report->photos()->create(['path' => 'reports/1/b.jpg', 'mime' => 'image/jpeg', 'size' => 10, 'sort_order' => 1]);
        $report->photos()->create(['path' => 'reports/1/a.jpg', 'mime' => 'image/jpeg', 'size' => 10, 'sort_order' => 0]);
        $report->tags()->attach(Tag::create(['name' => 'lampujalan']));

        $history = ReportStatusHistory::create([
            'report_id' => $report->id,
            'from_status' => ReportStatus::AdminReview,
            'to_status' => ReportStatus::Rejected,
            'actor_id' => null,
            'reason_category' => ReasonCategory::UnclearEvidence,
            'note' => 'Foto buram.',
        ]);

        $report->refresh();

        $this->assertSame(['reports/1/a.jpg', 'reports/1/b.jpg'], $report->photos->pluck('path')->all());
        $this->assertSame(['lampujalan'], $report->tags->pluck('name')->all());
        $this->assertCount(1, $report->statusHistories);
        $this->assertSame(ReportStatus::Rejected, $history->fresh()->to_status);
        $this->assertSame(ReasonCategory::UnclearEvidence, $history->fresh()->reason_category);
        $this->assertNull($history->fresh()->actor);
    }

    public function test_tag_names_are_unique(): void
    {
        Tag::create(['name' => 'jalanrusak']);

        $this->expectException(QueryException::class);

        Tag::create(['name' => 'jalanrusak']);
    }
}
