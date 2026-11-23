<?php

namespace App\Modules\Report\Tests\Feature;

use App\Modules\Auth\Models\User;
use App\Modules\Report\Models\Report;
use App\Modules\Report\Models\ReportStatusHistory;
use App\Modules\Report\Models\Tag;
use App\Modules\Report\Services\ReportPdfService;
use App\Modules\Report\Services\ReportQueryService;
use App\Modules\Shared\Enums\Criticality;
use App\Modules\Shared\Enums\ReportStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DownloadReportPdfTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        config(['filesystems.default' => 'public']);
        Storage::fake('public');

        $this->owner = User::factory()->create(['name' => 'Budi Santoso']);
    }

    private function report(ReportStatus $status = ReportStatus::Forwarded): Report
    {
        $report = Report::factory()->status($status)->create([
            'reporter_id' => $this->owner->id,
            'code' => 'GFR-2026-000123',
            'title' => 'Lampu jalan mati di Jl. Darmo',
            'description' => 'Tiga lampu mati sejak dua minggu.',
            'criticality' => Criticality::High,
            'revision' => 2,
        ]);

        $photo = UploadedFile::fake()->image('foto.png', 40, 30);
        Storage::disk('public')->put("reports/{$report->id}/foto.png", $photo->get());
        $report->photos()->create(['path' => "reports/{$report->id}/foto.png", 'mime' => 'image/png', 'size' => 70, 'sort_order' => 0]);
        $report->tags()->attach(Tag::query()->create(['name' => 'penerangan']));

        ReportStatusHistory::query()->create([
            'report_id' => $report->id,
            'from_status' => 'admin_review',
            'to_status' => 'rejected',
            'actor_id' => User::factory()->admin()->create(['name' => 'Admin Kota'])->id,
            'reason_category' => 'unclear_evidence',
            'note' => 'Foto kurang jelas, mohon foto ulang.',
        ]);

        return $report;
    }

    public function test_owner_downloads_the_pdf_named_after_the_report_code(): void
    {
        $report = $this->report();

        $response = $this->actingAs($this->owner, 'api')
            ->get("/api/v1/reports/{$report->id}/pdf")
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'attachment; filename="GFR-2026-000123.pdf"');

        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertStringContainsString('/Subtype /Image', $response->getContent(), 'The photo should be embedded.');
    }

    public function test_the_pdf_holds_the_code_content_status_history_and_print_date(): void
    {
        $report = $this->report();
        $this->travelTo(Carbon::parse('2026-11-24 03:05:00', 'UTC'));

        $html = app(ReportPdfService::class)->html(app(ReportQueryService::class)->findDetailOrFail($report->id));

        foreach ([
            'GFR-2026-000123',
            'Lampu jalan mati di Jl. Darmo',
            'Tiga lampu mati sejak dua minggu.',
            'Diteruskan',
            'Tinggi',
            'Budi Santoso',
            '#penerangan',
            'Admin Kota',
            '[Bukti kurang jelas]',
            'Foto kurang jelas, mohon foto ulang.',
            'Dicetak pada 24 November 2026, 10:05 WIB',
            'data:image/png;base64,',
        ] as $expected) {
            $this->assertStringContainsString($expected, $html);
        }

        $this->assertStringNotContainsString($this->owner->email, $html);
    }

    public function test_admin_downloads_any_report_and_government_only_forwarded_ones(): void
    {
        $forwarded = $this->report(ReportStatus::Forwarded);
        $inReview = Report::factory()->status(ReportStatus::AdminReview)->create(['reporter_id' => $this->owner->id]);

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin, 'api')->get("/api/v1/reports/{$inReview->id}/pdf")->assertOk();

        $government = User::factory()->government()->create();
        $this->actingAs($government, 'api')->get("/api/v1/reports/{$forwarded->id}/pdf")->assertOk();
        $this->actingAs($government, 'api')->getJson("/api/v1/reports/{$inReview->id}/pdf")->assertNotFound();
    }

    public function test_other_citizens_and_guests_cannot_download(): void
    {
        $report = $this->report();

        $this->getJson("/api/v1/reports/{$report->id}/pdf")->assertUnauthorized();

        $this->actingAs(User::factory()->create(), 'api')
            ->getJson("/api/v1/reports/{$report->id}/pdf")->assertNotFound();

        $this->actingAs($this->owner, 'api')
            ->getJson('/api/v1/reports/999999/pdf')->assertNotFound();
    }
}
