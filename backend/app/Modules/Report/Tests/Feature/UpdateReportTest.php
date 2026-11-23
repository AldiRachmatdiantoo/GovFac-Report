<?php

namespace App\Modules\Report\Tests\Feature;

use App\Modules\Auth\Models\User;
use App\Modules\Report\Events\ReportSubmitted;
use App\Modules\Report\Models\Report;
use App\Modules\Report\Models\ReportStatusHistory;
use App\Modules\Report\Models\Tag;
use App\Modules\Shared\Enums\ReportStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UpdateReportTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        config(['filesystems.default' => 'public']);
        Storage::fake('public');
        Event::fake([ReportSubmitted::class]);

        $this->owner = User::factory()->create();
    }

    private function report(ReportStatus $status = ReportStatus::Rejected): Report
    {
        $report = Report::factory()->status($status)->create([
            'reporter_id' => $this->owner->id,
            'title' => 'Lampu mati',
            'description' => 'Lampu mati.',
        ]);

        Storage::disk('public')->put("reports/{$report->id}/lama.png", 'old');
        $report->photos()->create(['path' => "reports/{$report->id}/lama.png", 'mime' => 'image/png', 'size' => 3, 'sort_order' => 0]);
        $report->tags()->attach(Tag::query()->create(['name' => 'lampu']));

        ReportStatusHistory::query()->create([
            'report_id' => $report->id,
            'from_status' => 'admin_review',
            'to_status' => $status->value,
            'actor_id' => User::factory()->admin()->create()->id,
            'reason_category' => 'incomplete',
            'note' => 'Deskripsi kurang jelas, sebutkan lokasinya.',
        ]);

        return $report;
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'title' => 'Lampu jalan mati di depan Taman Bungkul',
            'description' => 'Tiga lampu jalan di depan Taman Bungkul mati sejak dua minggu.',
            'tags' => ['#Penerangan', 'lampujalan'],
            ...$overrides,
        ];
    }

    private function photo(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='
        ));
    }

    public function test_owner_fixes_a_rejected_report_and_it_goes_back_to_ai_validation(): void
    {
        $report = $this->report();
        $oldPhotoId = $report->photos()->value('id');

        $this->actingAs($this->owner, 'api')
            ->putJson("/api/v1/reports/{$report->id}", $this->payload())
            ->assertOk()
            ->assertJsonPath('data.status', 'ai_validation')
            ->assertJsonPath('data.revision', 2)
            ->assertJsonPath('data.title', 'Lampu jalan mati di depan Taman Bungkul')
            ->assertJsonPath('data.tags', ['penerangan', 'lampujalan'])
            ->assertJsonPath('data.photos.0.id', $oldPhotoId)
            ->assertJsonCount(1, 'data.photos');

        $history = ReportStatusHistory::query()->where('report_id', $report->id)->orderBy('id')->get();
        $this->assertSame('Deskripsi kurang jelas, sebutkan lokasinya.', $history[0]->note);
        $this->assertSame(ReportStatus::Rejected, $history[1]->from_status);
        $this->assertSame(ReportStatus::AiValidation, $history[1]->to_status);
        $this->assertSame($this->owner->id, $history[1]->actor_id);

        Event::assertDispatched(ReportSubmitted::class, fn (ReportSubmitted $event) => $event->report->is($report) && $event->report->revision === 2);
    }

    public function test_new_photos_sent_as_multipart_replace_the_old_ones(): void
    {
        $report = $this->report();

        $this->actingAs($this->owner, 'api')
            ->post("/api/v1/reports/{$report->id}", $this->payload([
                '_method' => 'PUT',
                'photos' => [$this->photo('satu.png'), $this->photo('dua.png')],
            ]), ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonCount(2, 'data.photos');

        Storage::disk('public')->assertMissing("reports/{$report->id}/lama.png");
        $this->assertCount(2, Storage::disk('public')->files("reports/{$report->id}"));
        $this->assertSame([0, 1], $report->photos()->pluck('sort_order')->all());
    }

    /**
     * @return array<string, array{ReportStatus}>
     */
    public static function notEditable(): array
    {
        $statuses = array_filter(ReportStatus::cases(), fn (ReportStatus $status) => $status !== ReportStatus::Rejected);

        return array_combine(
            array_map(fn (ReportStatus $status) => $status->value, $statuses),
            array_map(fn (ReportStatus $status) => [$status], $statuses),
        );
    }

    #[DataProvider('notEditable')]
    public function test_editing_any_other_status_is_rejected_with_409(ReportStatus $status): void
    {
        $report = $this->report($status);

        $this->actingAs($this->owner, 'api')
            ->putJson("/api/v1/reports/{$report->id}", $this->payload())
            ->assertConflict()
            ->assertJsonPath('code', 'invalid_status_transition')
            ->assertJsonPath('current_status', $status->value);

        $report->refresh();
        $this->assertSame('Lampu mati', $report->title);
        $this->assertSame(1, $report->revision);
        Event::assertNotDispatched(ReportSubmitted::class);
    }

    public function test_only_the_owner_may_edit(): void
    {
        $report = $this->report();

        $this->putJson("/api/v1/reports/{$report->id}", $this->payload())->assertUnauthorized();

        $this->actingAs(User::factory()->create(), 'api')
            ->putJson("/api/v1/reports/{$report->id}", $this->payload())->assertNotFound();

        $this->actingAs(User::factory()->admin()->create(), 'api')
            ->putJson("/api/v1/reports/{$report->id}", $this->payload())->assertForbidden();

        $this->actingAs($this->owner, 'api')
            ->putJson('/api/v1/reports/999999', $this->payload())->assertNotFound();

        $this->assertSame(1, $report->fresh()->revision);
    }

    public function test_input_is_validated_like_a_new_report(): void
    {
        $report = $this->report();

        $this->actingAs($this->owner, 'api')
            ->putJson("/api/v1/reports/{$report->id}", $this->payload([
                'title' => str_repeat('a', 101),
                'tags' => [],
                'photos' => [],
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'tags', 'photos']);
    }
}
