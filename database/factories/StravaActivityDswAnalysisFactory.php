<?php

namespace Database\Factories;

use App\Models\StravaActivityDswAnalysis;
use Illuminate\Database\Eloquent\Factories\Factory;

class StravaActivityDswAnalysisFactory extends Factory
{
    protected $model = StravaActivityDswAnalysis::class;

    public function definition(): array
    {
        return [
            'intervals' => $this->faker->boolean(),
            'treadmill' => $this->faker->boolean(),
            'dsw_score' => $this->faker->randomNumber(),
            'notes' => $this->faker->word(),
        ];
    }
}
