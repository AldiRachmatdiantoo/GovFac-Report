<?php

namespace App\Modules\AiValidation\Contracts;

use App\Modules\Shared\Enums\Criticality;
use Carbon\CarbonInterface;

/**
 * A stored AI validation result, as other modules may read it.
 */
final readonly class RecordedAiValidation
{
    /**
     * @param  list<string>  $reasons
     */
    public function __construct(
        public int $revision,
        public AiVerdict $verdict,
        public ?float $score,
        public ?Criticality $criticality,
        public array $reasons,
        public ?string $model,
        public ?CarbonInterface $validatedAt,
    ) {}

    /**
     * @return array{revision: int, verdict: string, score: float|null, criticality: string|null, reasons: list<string>, model: string|null, validated_at: string|null}
     */
    public function toArray(): array
    {
        return [
            'revision' => $this->revision,
            'verdict' => $this->verdict->value,
            'score' => $this->score,
            'criticality' => $this->criticality?->value,
            'reasons' => $this->reasons,
            'model' => $this->model,
            'validated_at' => $this->validatedAt?->toIso8601ZuluString(),
        ];
    }
}
