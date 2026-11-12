<?php

namespace App\Modules\Report\Repositories;

use App\Modules\Report\Models\Tag;
use Illuminate\Database\Eloquent\Collection;

class TagRepository
{
    public function firstOrCreate(string $name): Tag
    {
        return Tag::query()->firstOrCreate(['name' => $name]);
    }

    /** @return Collection<int, Tag> */
    public function suggest(string $prefix): Collection
    {
        $escaped = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $prefix);

        return Tag::query()->withCount('reports')
            ->whereRaw("name LIKE ? ESCAPE '!'", [$escaped.'%'])
            ->orderByDesc('reports_count')->orderBy('name')->limit(10)->get();
    }
}
