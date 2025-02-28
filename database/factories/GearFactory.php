<?php

namespace Database\Factories;

use App\Models\Gear;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class GearFactory extends Factory
{
    protected $model = Gear::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'description' => $this->faker->text(),
            'image_path' => $this->faker->filePath(),
            'first_used' => $this->faker->date(),
            'decommissioned' => $this->faker->date(),
        ];
    }
}
