<?php

namespace Tests\Unit\Console\Commands;

use App\Services\Strava\Webhooks\StravaWebhooksService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class RegisterStravaWebhookEventsSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_subscription_registration_with_no_existing_subscription(): void
    {
        $mockedStravaWebhooksService = Mockery::mock(StravaWebhooksService::class);
        $mockedStravaWebhooksService->shouldReceive('checkExistingSubscription')
            ->once()
            ->andReturnNull();
        $mockedStravaWebhooksService->shouldReceive('createAndStoreNewSubscription')
            ->once();
        app()->instance(StravaWebhooksService::class, $mockedStravaWebhooksService);

        $this->artisan('strava:register-webhook-events-subscription')
            ->expectsOutput('Subscription created successfully!')
            ->assertExitCode(0);
    }

    public function test_subscription_registration_with_existing_subscription(): void
    {
        $mockedStravaWebhooksService = Mockery::mock(StravaWebhooksService::class);
        $mockedStravaWebhooksService->shouldReceive('checkExistingSubscription')
            ->once()
            ->andReturn(123);
        $mockedStravaWebhooksService->shouldNotReceive('createAndStoreNewSubscription');
        app()->instance(StravaWebhooksService::class, $mockedStravaWebhooksService);

        $this->artisan('strava:register-webhook-events-subscription')
            ->expectsOutput('Subscription already exists with ID: 123')
            ->assertExitCode(0);
    }
}
