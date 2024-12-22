<?php

namespace App\Console\Commands;

use App\Services\Strava\Webhooks\StravaWebhooksService;
use Illuminate\Console\Command;

class RegisterStravaWebhookEventsSubscription extends Command
{
    protected $signature = 'strava:register-webhook-events-subscription';

    protected $description = 'Subscribe to Strava webhook events.';

    public function handle(): void
    {
        $existingSubscriptionId = app(StravaWebhooksService::class)->checkExistingSubscription();

        if ($existingSubscriptionId) {
            $this->error("Subscription already exists with ID: {$existingSubscriptionId}");

            return;
        }

        app(StravaWebhooksService::class)->createAndStoreNewSubscription();

        $this->info('Subscription created successfully!');
    }
}
