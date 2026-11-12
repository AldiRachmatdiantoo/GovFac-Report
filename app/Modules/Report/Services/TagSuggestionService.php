<?php

namespace App\Modules\Report\Services;

use App\Modules\Report\Repositories\TagRepository;
use Illuminate\Support\Collection;

class TagSuggestionService
{
    public function __construct(private TagRepository $tags) {}

    /** @return Collection<int, array{id: int, name: string}> */
    public function suggest(string $prefix): Collection
    {
        return $this->tags->suggest(mb_strtolower(ltrim($prefix, '#')))
            ->map(fn ($tag): array => ['id' => $tag->id, 'name' => $tag->name]);
    }
}
