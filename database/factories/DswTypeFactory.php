<?php

namespace Database\Factories;

use App\Models\DswType;
use Illuminate\Database\Eloquent\Factories\Factory;

class DswTypeFactory extends Factory
{
    protected $model = DswType::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
