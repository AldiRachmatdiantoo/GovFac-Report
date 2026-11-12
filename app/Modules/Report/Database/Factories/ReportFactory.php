<?php

namespace App\Modules\Report\Database\Factories;

use App\Modules\Auth\Models\User;
use App\Modules\Report\Models\Report;
use App\Modules\Shared\Enums\ReportStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Report>
 */
class ReportFactory extends Factory
{
    protected $model = Report::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'GFR-'.now()->year.'-'.fake()->unique()->numerify('######'),
            'reporter_id' => User::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'status' => ReportStatus::AiValidation,
            'revision' => 1,
            'likes_count' => 0,
        ];
    }

    public function status(ReportStatus $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }
}
