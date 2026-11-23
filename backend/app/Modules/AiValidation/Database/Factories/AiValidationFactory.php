<?php

namespace App\Modules\AiValidation\Database\Factories;

use App\Modules\AiValidation\Contracts\AiVerdict;
use App\Modules\AiValidation\Models\AiValidation;
use App\Modules\Report\Models\Report;
use App\Modules\Shared\Enums\Criticality;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiValidation>
 */
class AiValidationFactory extends Factory
{
    protected $model = AiValidation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'report_id' => Report::factory(),
            'revision' => 1,
            'verdict' => AiVerdict::Pass,
            'score' => fake()->randomFloat(2, 0.5, 1),
            'criticality' => fake()->randomElement(Criticality::cases()),
            'reasons' => [],
            'model' => 'test-model',
        ];
    }

    public function verdict(AiVerdict $verdict): static
    {
        return $this->state(fn () => ['verdict' => $verdict]);
    }
}
