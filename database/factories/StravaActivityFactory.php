<?php

namespace Database\Factories;

use App\Models\StravaActivity;
use Illuminate\Database\Eloquent\Factories\Factory;

class StravaActivityFactory extends Factory
{
    protected $model = StravaActivity::class;

    public function definition(): array
    {
        return [
            'is_summary' => $this->faker->boolean(),
            'name' => $this->faker->name(),
            'description' => $this->faker->sentence(),
            'distance_meters' => $this->faker->randomFloat(2, 0, 10000),
            'moving_time_seconds' => $this->faker->randomNumber(2),
            'elapsed_time_seconds' => $this->faker->randomNumber(2),
            'elevation_gain_meters' => $this->faker->randomFloat(2, 0, 1000),
            'started_at' => $this->faker->dateTime(),
            'timezone' => $this->faker->timezone(),
            'summary_polyline' => $this->faker->sentence(),
            'average_speed_meters_per_second' => $this->faker->randomFloat(2, 0, 10),
            'max_speed_meters_per_second' => $this->faker->randomFloat(2, 0, 10),
            'average_heartrate' => $this->faker->randomFloat(2, 0, 200),
            'max_heartrate' => $this->faker->randomFloat(2, 0, 200),
            'average_watts' => $this->faker->randomFloat(2, 0, 500),
            'max_watts' => $this->faker->randomFloat(2, 0, 500),
        ];
    }
}
