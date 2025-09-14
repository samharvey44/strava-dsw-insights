<?php

namespace Database\Factories;

use App\Models\GearReminder;
use Illuminate\Database\Eloquent\Factories\Factory;

class GearReminderFactory extends Factory
{
    protected $model = GearReminder::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'trigger_after_number_of_activities' => $this->faker->numberBetween(1, 10),
            'current_number_of_activities' => $this->faker->numberBetween(0, 10),
            'last_triggered' => $this->faker->dateTime(),
        ];
    }
}
