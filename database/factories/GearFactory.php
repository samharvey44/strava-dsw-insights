<?php

namespace Database\Factories;

use App\Models\Gear;
use Illuminate\Database\Eloquent\Factories\Factory;

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
            'auto_attach_to_activities' => $this->faker->boolean(),
        ];
    }
}
