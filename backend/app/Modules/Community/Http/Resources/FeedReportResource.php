<?php

namespace App\Modules\Community\Http\Resources;

use App\Modules\Report\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * A feed item: the report object from the API contract without the description,
 * with only the first photo, and without any contact data of the reporter.
 *
 * @mixin Report
 */
class FeedReportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $photo = $this->photos->first();

        return [
            'id' => $this->id,
            'code' => $this->code,
            'title' => $this->title,
            'status' => $this->status->value,
            'criticality' => $this->criticality?->value,
            'tags' => $this->tags->pluck('name')->values(),
            'photos' => $photo === null ? [] : [['id' => $photo->id, 'url' => $this->photoUrl($photo->path)]],
            'likes_count' => $this->likes_count,
            'liked_by_me' => (bool) $this->liked_by_me,
            'reporter' => ['id' => $this->reporter->id, 'name' => $this->reporter->name],
            'schedule' => null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
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
