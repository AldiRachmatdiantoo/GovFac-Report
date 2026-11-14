<?php

namespace App\Modules\Report\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ReportPhotoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $disk = Storage::disk(config('filesystems.default'));

        return [
            'id' => $this->id,
            'url' => $disk->providesTemporaryUrls()
                ? $disk->temporaryUrl($this->path, now()->addMinutes(15))
                : $disk->url($this->path),
        ];
    }
}
