<?php

namespace Database\Factories;

use App\Models\StravaRawActivity;
use Illuminate\Database\Eloquent\Factories\Factory;

class StravaRawActivityFactory extends Factory
{
    protected $model = StravaRawActivity::class;

    public function definition(): array
    {
        return [
            'strava_activity_id' => $this->faker->randomNumber(),
            'data' => $this->faker->words(),
        ];
    }
}
