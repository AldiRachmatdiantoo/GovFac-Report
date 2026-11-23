<?php

namespace App\Modules\AiValidation\Services;

use App\Modules\AiValidation\Contracts\AiValidationResult;
use App\Modules\AiValidation\Contracts\AiValidator;
use App\Modules\AiValidation\Contracts\AiVerdict;
use App\Modules\Report\Models\Report;
use App\Modules\Shared\Enums\Criticality;
use App\Modules\Shared\Enums\ReasonCategory;
use Illuminate\Support\Str;

/**
 * Stand-in until we pick an AI provider (an open question in the PRD).
 * It can't look at photos, so it only checks the text.
 */
class RuleBasedAiValidator implements AiValidator
{
    public const MODEL = 'rule-based-v1';

    private const MIN_DESCRIPTION_LENGTH = 20;

    private const HIGH_CRITICALITY_WORDS = [
        'bahaya', 'roboh', 'ambruk', 'runtuh', 'longsor', 'banjir', 'tumbang', 'korsleting', 'kecelakaan', 'korban',
    ];

    private const LOW_CRITICALITY_WORDS = ['kotor', 'coretan', 'vandal', 'pudar', 'kusam'];

    public function validate(Report $report): AiValidationResult
    {
        $criticality = $this->criticality($report);

        if (mb_strlen(trim($report->description)) < self::MIN_DESCRIPTION_LENGTH) {
            return new AiValidationResult(
                verdict: AiVerdict::Fail,
                reasons: ['Deskripsi terlalu singkat. Jelaskan kerusakan minimal '.self::MIN_DESCRIPTION_LENGTH.' karakter.'],
                criticality: $criticality,
                model: self::MODEL,
                reasonCategory: ReasonCategory::Incomplete,
            );
        }

        return new AiValidationResult(AiVerdict::Pass, criticality: $criticality, model: self::MODEL);
    }

    private function criticality(Report $report): Criticality
    {
        $text = mb_strtolower(implode(' ', [$report->title, $report->description, ...$report->tags->pluck('name')]));

        return match (true) {
            Str::contains($text, self::HIGH_CRITICALITY_WORDS) => Criticality::High,
            Str::contains($text, self::LOW_CRITICALITY_WORDS) => Criticality::Low,
            default => Criticality::Medium,
        };
    }
}
