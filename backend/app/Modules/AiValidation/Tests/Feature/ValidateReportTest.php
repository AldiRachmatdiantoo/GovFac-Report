<?php

namespace App\Modules\AiValidation\Tests\Feature;

use App\Modules\AiValidation\Contracts\AiValidator;
use App\Modules\AiValidation\Events\ReportValidated;
use App\Modules\AiValidation\Jobs\ValidateReport;
use App\Modules\AiValidation\Models\AiValidation;
use App\Modules\AiValidation\Services\RuleBasedAiValidator;
use App\Modules\Auth\Models\User;
use App\Modules\Report\Events\ReportSubmitted;
use App\Modules\Report\Models\Report;
use App\Modules\Report\Models\ReportStatusHistory;
use App\Modules\Shared\Enums\Criticality;
use App\Modules\Shared\Enums\ReasonCategory;
use App\Modules\Shared\Enums\ReportStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class ValidateReportTest extends TestCase
{
    use RefreshDatabase;

    private function report(string $description, ReportStatus $status = ReportStatus::AiValidation, int $revision = 1): Report
    {
        return Report::factory()->status($status)->create(['description' => $description, 'revision' => $revision]);
    }

    private function lastHistory(Report $report): ReportStatusHistory
    {
        return ReportStatusHistory::query()->where('report_id', $report->id)->latest('id')->firstOrFail();
    }

    public function test_submitting_a_report_queues_validation_and_answers_before_it_runs(): void
    {
        Queue::fake();
        config(['filesystems.default' => 'public']);
        Storage::fake('public');

        $photo = UploadedFile::fake()->createWithContent('foto.png', base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='
        ));

        $id = $this->actingAs(User::factory()->create(), 'api')
            ->post('/api/v1/reports', [
                'title' => 'Lampu jalan mati',
                'description' => 'Tiga lampu di depan taman mati sejak dua minggu.',
                'photos' => [$photo],
                'tags' => ['penerangan'],
            ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.status', 'ai_validation')
            ->json('data.id');

        Queue::assertPushed(ValidateReport::class, fn (ValidateReport $job) => $job->report->id === $id && $job->revision === 1);
        $this->assertDatabaseCount('ai_validations', 0);
    }

    public function test_a_passing_report_moves_to_admin_review_with_its_criticality(): void
    {
        Event::fake([ReportValidated::class]);
        $report = $this->report('Pohon besar tumbang menutup separuh jalan di depan sekolah.');

        ReportSubmitted::dispatch($report);

        $report->refresh();
        $this->assertSame(ReportStatus::AdminReview, $report->status);
        $this->assertSame(Criticality::High, $report->criticality);

        $validation = AiValidation::query()->sole();
        $this->assertSame('pass', $validation->verdict->value);
        $this->assertSame(1, $validation->revision);
        $this->assertSame(Criticality::High, $validation->criticality);
        $this->assertSame([], $validation->reasons);
        $this->assertSame(RuleBasedAiValidator::MODEL, $validation->model);

        $history = $this->lastHistory($report);
        $this->assertSame(ReportStatus::AiValidation, $history->from_status);
        $this->assertSame(ReportStatus::AdminReview, $history->to_status);
        $this->assertNull($history->actor_id);

        Event::assertDispatched(ReportValidated::class, fn (ReportValidated $event) => $event->validation->is($validation));
    }

    public function test_a_failing_report_is_rejected_by_the_system_with_the_reason_as_note(): void
    {
        $report = $this->report('Rusak.');

        ReportSubmitted::dispatch($report);

        $this->assertSame(ReportStatus::Rejected, $report->fresh()->status);
        $this->assertSame('fail', AiValidation::query()->sole()->verdict->value);

        $history = $this->lastHistory($report);
        $this->assertSame(ReportStatus::Rejected, $history->to_status);
        $this->assertNull($history->actor_id);
        $this->assertSame(ReasonCategory::Incomplete, $history->reason_category);
        $this->assertStringContainsString('Deskripsi terlalu singkat', $history->note);
    }

    public function test_when_every_attempt_fails_the_report_still_reaches_the_admin_as_flag(): void
    {
        $report = $this->report('Pohon besar tumbang menutup separuh jalan di depan sekolah.');
        $this->mock(AiValidator::class)->shouldReceive('validate')->andThrow(new RuntimeException('provider down'));

        $this->assertSame(3, (new ValidateReport($report, 1))->tries);

        // The sync queue runs a single attempt and then calls failed(), like the worker after the last retry.
        try {
            ReportSubmitted::dispatch($report);
            $this->fail('Expected the provider error to surface.');
        } catch (RuntimeException $exception) {
            $this->assertSame('provider down', $exception->getMessage());
        }

        $report->refresh();
        $this->assertSame(ReportStatus::AdminReview, $report->status);
        $this->assertNull($report->criticality);

        $validation = AiValidation::query()->sole();
        $this->assertSame('flag', $validation->verdict->value);
        $this->assertSame(['Validasi AI tidak tersedia.'], $validation->reasons);
        $this->assertNull($validation->score);
        $this->assertSame('Validasi AI tidak tersedia.', $this->lastHistory($report)->note);
    }

    public function test_a_report_cancelled_or_resubmitted_meanwhile_is_left_alone(): void
    {
        $cancelled = $this->report('Pohon besar tumbang menutup separuh jalan.', ReportStatus::Cancelled);
        $newerRevision = $this->report('Pohon besar tumbang menutup separuh jalan.', revision: 2);

        ValidateReport::dispatchSync($cancelled, 1);
        ValidateReport::dispatchSync($newerRevision, 1);

        $this->assertDatabaseCount('ai_validations', 0);
        $this->assertSame(ReportStatus::Cancelled, $cancelled->fresh()->status);
        $this->assertSame(ReportStatus::AiValidation, $newerRevision->fresh()->status);
    }
}
