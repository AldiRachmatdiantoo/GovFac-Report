<?php

namespace App\Modules\Report\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name'])]
class Tag extends Model
{
    public const UPDATED_AT = null;

    /** @return BelongsToMany<Report, $this> */
    public function reports(): BelongsToMany
    {
        return $this->belongsToMany(Report::class, 'report_tag');
    }
}
