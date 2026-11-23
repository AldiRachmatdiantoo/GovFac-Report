<?php

namespace App\Modules\AiValidation\Contracts;

use App\Modules\Shared\Enums\Criticality;
use App\Modules\Shared\Enums\ReasonCategory;

final readonly class AiValidationResult
{
    /**
     * @param  list<string>  $reasons  shown to the admin, and to the reporter when the verdict is `fail`
     */
    public function __construct(
        public AiVerdict $verdict,
        public array $reasons = [],
        public ?Criticality $criticality = null,
        public ?float $score = null,
        public ?string $model = null,
        public ?ReasonCategory $reasonCategory = null,
    ) {}

    public static function unavailable(): self
    {
        return new self(AiVerdict::Flag, ['Validasi AI tidak tersedia.']);
    }

    public function note(): ?string
    {
        return $this->reasons === [] ? null : implode(' ', $this->reasons);
    }
}
