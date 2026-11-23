<?php

namespace App\Modules\Report\Services;

use App\Modules\Report\Models\Report;
use App\Modules\Report\Models\ReportPhoto;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Storage;

class ReportPdfService
{
    // Same wording as STATUS_LABEL and REASON_LABEL in the frontend.
    private const STATUS_LABELS = [
        'ai_validation' => 'Validasi AI',
        'admin_review' => 'Ditinjau Admin',
        'approved' => 'Disetujui Admin',
        'forwarded' => 'Diteruskan',
        'gov_approved' => 'Disetujui Pemerintah',
        'scheduled' => 'Terjadwal',
        'rejected' => 'Ditolak',
        'cancelled' => 'Dibatalkan',
    ];

    private const REASON_LABELS = [
        'duplicate' => 'Duplikat',
        'unclear_evidence' => 'Bukti kurang jelas',
        'not_public_facility' => 'Bukan fasilitas umum',
        'incomplete' => 'Tidak lengkap',
        'other' => 'Lainnya',
    ];

    private const CRITICALITY_LABELS = [
        'low' => 'Rendah',
        'medium' => 'Sedang',
        'high' => 'Tinggi',
    ];

    public function __construct(private readonly ReportQueryService $queries) {}

    /**
     * Expects the report with photos, tags and reporter loaded, as findDetailOrFail() returns it.
     */
    public function render(Report $report): string
    {
        return Pdf::loadHTML($this->html($report))
            ->setPaper('a4')
            // Without subsetting every PDF carries the whole DejaVu font, close to 1 MB.
            ->setOption('isFontSubsettingEnabled', true)
            ->output();
    }

    public function html(Report $report): string
    {
        return view('report.pdf', [
            'report' => $report,
            'timeline' => $this->queries->timeline($report),
            'photos' => $this->embeddedPhotos($report),
            'statusLabels' => self::STATUS_LABELS,
            'reasonLabels' => self::REASON_LABELS,
            'criticalityLabels' => self::CRITICALITY_LABELS,
            'formatDate' => fn (?CarbonInterface $at): string => $at === null ? '-'
                : $at->copy()->timezone('Asia/Jakarta')->locale('id')->translatedFormat('j F Y, H:i').' WIB',
            'printedAt' => now(),
        ])->render();
    }

    /**
     * dompdf may not fetch files on its own, so we inline the photos as data URIs.
     *
     * @return list<string>
     */
    private function embeddedPhotos(Report $report): array
    {
        $disk = Storage::disk(config('filesystems.default'));

        return $report->photos
            ->filter(fn (ReportPhoto $photo): bool => $disk->exists($photo->path))
            ->map(fn (ReportPhoto $photo): string => 'data:'.$photo->mime.';base64,'.base64_encode($disk->get($photo->path)))
            ->values()
            ->all();
    }
}
