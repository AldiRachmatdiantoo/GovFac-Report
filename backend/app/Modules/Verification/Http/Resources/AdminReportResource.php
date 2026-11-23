<?php

namespace App\Modules\Verification\Http\Resources;

use App\Modules\Report\Models\ReportPhoto;
use App\Modules\Verification\Services\ReviewedReport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * The report object from the API contract plus the AI result, for the admin.
 *
 * @property ReviewedReport $resource
 */
class AdminReportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $report = $this->resource->report;

        return [
            'id' => $report->id,
            'code' => $report->code,
            'title' => $report->title,
            'description' => $report->description,
            'status' => $report->status->value,
            'criticality' => $report->criticality?->value,
            'revision' => $report->revision,
            'tags' => $report->tags->pluck('name')->values(),
            'photos' => $report->photos->map(fn (ReportPhoto $photo): array => [
                'id' => $photo->id,
                'url' => $this->photoUrl($photo->path),
            ])->values(),
            'likes_count' => $report->likes_count,
            'reporter' => ['id' => $report->reporter->id, 'name' => $report->reporter->name],
            'ai_validation' => $this->resource->aiValidation?->toArray(),
            'created_at' => $report->created_at,
            'updated_at' => $report->updated_at,
        ];
    }

    private function photoUrl(string $path): string
    {
        $disk = Storage::disk(config('filesystems.default'));

        return $disk->providesTemporaryUrls()
            ? $disk->temporaryUrl($path, now()->addMinutes(15))
            : $disk->url($path);
    }
}
