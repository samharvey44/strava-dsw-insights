<?php

namespace Database\Factories;

use App\Models\StravaWebhookSubscription;
use Illuminate\Database\Eloquent\Factories\Factory;

class StravaWebhookSubscriptionFactory extends Factory
{
    protected $model = StravaWebhookSubscription::class;

    public function definition(): array
    {
        return [
            'strava_subscription_id' => $this->faker->randomNumber(),
        ];
    }
}
