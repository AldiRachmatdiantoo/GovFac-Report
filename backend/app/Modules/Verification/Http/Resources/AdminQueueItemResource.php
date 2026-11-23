<?php

namespace App\Modules\Verification\Http\Resources;

use App\Modules\Report\Models\Report;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * A list item of the admin queue: no description and only the first photo.
 *
 * @mixin Report
 */
class AdminQueueItemResource extends JsonResource
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
            'revision' => $this->revision,
            'tags' => $this->tags->pluck('name')->values(),
            'photos' => $photo === null ? [] : [['id' => $photo->id, 'url' => $this->photoUrl($photo->path)]],
            'likes_count' => $this->likes_count,
            'reporter' => ['id' => $this->reporter->id, 'name' => $this->reporter->name],
            'waiting_since' => $this->waiting_since === null ? null : CarbonImmutable::parse($this->waiting_since, 'UTC'),
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
