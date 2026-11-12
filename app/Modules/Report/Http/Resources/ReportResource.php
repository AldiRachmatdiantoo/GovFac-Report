<?php

namespace App\Modules\Report\Http\Resources;

use App\Modules\Report\Models\Report;
use App\Modules\Report\Models\ReportPhoto;
use App\Modules\Report\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Report
 */
class ReportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status instanceof \BackedEnum ? $this->status->value : $this->status,
            'criticality' => $this->criticality instanceof \BackedEnum ? $this->criticality->value : $this->criticality,
            'revision' => $this->revision,
            'likes_count' => $this->likes_count,
            'tags' => $this->whenLoaded('tags', fn () => $this->tags->map(fn (Tag $t) => [
                'id' => $t->id,
                'name' => $t->name,
            ])),
            'photos' => $this->whenLoaded('photos', fn () => $this->photos->map(fn (ReportPhoto $p) => [
                'id' => $p->id,
                'path' => $p->path,
                'sort_order' => $p->sort_order,
            ])),
            'reporter' => $this->whenLoaded('reporter', fn () => $this->reporter ? [
                'id' => $this->reporter->id,
                'name' => $this->reporter->name,
            ] : null),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
