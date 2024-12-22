<?php

namespace Tests\Feature\Strava\Webhooks;

use App\Jobs\DeauthoriseStravaAthleteJob;
use App\Models\StravaConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Queue;
use Tests\TestCase;

class StravaWebhooksTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_webhook_verification(): void
    {
        config(['strava.webhook_verify_token' => 'verify-token']);

        $response = $this->get(
            route('strava.webhook-updates', [
                'hub_mode' => 'subscribe',
                'hub_verify_token' => 'verify-token',
                'hub_challenge' => 'challenge',
            ])
        );

        $response->assertOk();
        $response->assertJson([
            'hub.challenge' => 'challenge',
        ]);
    }

    public function test_unsuccessful_webhook_verification(): void
    {
        config(['strava.webhook_verify_token' => 'verify-token']);

        $response = $this->get(
            route('strava.webhook-updates', [
                'hub_mode' => 'subscribe',
                'hub_verify_token' => 'not-verify-token',
                'hub_challenge' => 'challenge',
            ])
        );

        $response->assertBadRequest();
    }

    public function test_athlete_deauthorisation_request(): void
    {
        Queue::fake();

        StravaConnection::factory()->create([
            'athlete_id' => 123,
            'user_id' => User::factory()->create()->id,
        ]);

        $response = $this->postJson(
            route('strava.webhook-updates'),
            [
                'updates' => [
                    'authorized' => 'false',
                ],
                'owner_id' => 123,
            ]
        );

        $response->assertOk();

        Queue::assertPushed(DeauthoriseStravaAthleteJob::class, function ($job) {
            return $job->stravaAthleteId === 123;
        });
        Queue::assertCount(1);
    }
}
