<?php

namespace App\Console\Commands;

use App\Models\StravaWebhookSubscription;
use App\Services\Strava\Webhooks\StravaWebhooksService;
use Illuminate\Console\Command;

class DeleteStravaWebhookEventsSubscription extends Command
{
    protected $signature = 'strava:delete-webhook-events-subscription';

    protected $description = 'Subscribe to Strava webhook events.';

    public function handle(): void
    {
        $subscription = StravaWebhookSubscription::latest()->first();

        if (! $subscription) {
            $this->error('No subscription found!');

            return;
        }

        app(StravaWebhooksService::class)->deleteExistingSubscription();

        $this->info('Subscription deleted successfully!');
    }
}
