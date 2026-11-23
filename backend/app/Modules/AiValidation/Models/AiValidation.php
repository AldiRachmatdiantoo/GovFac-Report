<?php

namespace App\Modules\AiValidation\Models;

use App\Modules\AiValidation\Contracts\AiVerdict;
use App\Modules\AiValidation\Database\Factories\AiValidationFactory;
use App\Modules\Report\Models\Report;
use App\Modules\Shared\Enums\Criticality;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['report_id', 'revision', 'verdict', 'score', 'criticality', 'reasons', 'model'])]
class AiValidation extends Model
{
    /** @use HasFactory<AiValidationFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'revision' => 'integer',
            'verdict' => AiVerdict::class,
            'score' => 'float',
            'criticality' => Criticality::class,
            'reasons' => 'array',
        ];
    }

    /** @return BelongsTo<Report, $this> */
    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    protected static function newFactory(): AiValidationFactory
    {
        return AiValidationFactory::new();
    }
}
