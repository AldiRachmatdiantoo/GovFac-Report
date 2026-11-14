<?php

namespace App\Modules\Report\Models;

use App\Modules\Auth\Models\User;
use App\Modules\Report\Database\Factories\ReportFactory;
use App\Modules\Shared\Enums\Criticality;
use App\Modules\Shared\Enums\ReportStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * `status` sengaja tidak fillable: status hanya berubah lewat ReportStatusService.
 */
#[Fillable(['code', 'reporter_id', 'title', 'description', 'criticality', 'revision'])]
class Report extends Model
{
    /** @use HasFactory<ReportFactory> */
    use HasFactory;

    protected $attributes = [
        'status' => 'ai_validation',
        'revision' => 1,
        'likes_count' => 0,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ReportStatus::class,
            'criticality' => Criticality::class,
            'revision' => 'integer',
            'likes_count' => 'integer',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    /** @return HasMany<ReportPhoto, $this> */
    public function photos(): HasMany
    {
        return $this->hasMany(ReportPhoto::class)->orderBy('sort_order');
    }

    /** @return BelongsToMany<Tag, $this> */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'report_tag');
    }

    /** @return HasMany<ReportStatusHistory, $this> */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(ReportStatusHistory::class)->orderBy('created_at')->orderBy('id');
    }

    protected static function newFactory(): ReportFactory
    {
        return ReportFactory::new();
    }
}
