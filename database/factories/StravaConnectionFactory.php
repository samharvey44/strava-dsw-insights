<?php

namespace Database\Factories;

use App\Models\StravaConnection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class StravaConnectionFactory extends Factory
{
    protected $model = StravaConnection::class;

    public function definition(): array
    {
        return [
            'athlete_id' => $this->faker->randomNumber(),
            'access_token' => encrypt(Str::random(10)),
            'access_token_expiry' => $this->faker->unixTime(),
            'refresh_token' => encrypt(Str::random(10)),
            'active' => $this->faker->boolean(),
        ];
    }
}
