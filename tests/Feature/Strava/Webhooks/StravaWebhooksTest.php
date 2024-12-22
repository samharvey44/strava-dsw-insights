<?php

namespace Strava\Webhooks;

use Illuminate\Foundation\Testing\RefreshDatabase;
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

    // TODO - any other webhook handling tests
}
