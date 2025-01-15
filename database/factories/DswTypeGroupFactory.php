<?php

namespace Database\Factories;

use App\Models\DswTypeGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

class DswTypeGroupFactory extends Factory
{
    protected $model = DswTypeGroup::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'display_class' => $this->faker->word(),
            'has_intervals' => $this->faker->boolean(),
        ];
    }
}
