<?php

namespace Tests\Unit\Console\Commands;

use App\Models\StravaWebhookSubscription;
use App\Services\Strava\Webhooks\StravaWebhooksService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class DeleteStravaWebhookEventsSubscriptionCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_deletion_of_existing_subscription(): void
    {
        StravaWebhookSubscription::factory()->create();

        $mockedStravaWebhooksService = Mockery::mock(StravaWebhooksService::class);
        $mockedStravaWebhooksService->shouldReceive('deleteExistingSubscription')
            ->once();
        app()->instance(StravaWebhooksService::class, $mockedStravaWebhooksService);

        $this->artisan('strava:delete-webhook-events-subscription')
            ->expectsOutput('Subscription deleted successfully!')
            ->assertExitCode(0);
    }

    public function test_deletion_of_non_existent_subscription(): void
    {
        $this->artisan('strava:delete-webhook-events-subscription')
            ->expectsOutput('No subscription found!')
            ->assertExitCode(0);
    }
}
