<?php

namespace App\Services\Strava\Webhooks;

use App\Http\Integrations\Strava\StravaHttpClient;
use App\Models\StravaWebhookSubscription;

class StravaWebhooksService
{
    public function checkExistingSubscription(): ?int
    {
        $response = app(StravaHttpClient::class)->get('push_subscriptions', [
            'client_id' => config('strava.client_id'),
            'client_secret' => config('strava.client_secret'),
        ]);

        if ($response->failed()) {
            $response->throw();
        }

        return count($response->json()) ? $response->json()[0]['id'] : null;
    }

    public function createAndStoreNewSubscription(): void
    {
        $response = app(StravaHttpClient::class)->post('push_subscriptions', [
            'client_id' => config('strava.client_id'),
            'client_secret' => config('strava.client_secret'),
            'callback_url' => config('strava.webhook_callback_uri') ?? route('webhook-updates'),
            'verify_token' => config('strava.webhook_verify_token'),
        ]);

        if ($response->failed()) {
            $response->throw();
        }

        // Purge any existing records we have, since Strava only allows one subscription at a time.
        StravaWebhookSubscription::delete();

        StravaWebhookSubscription::create([
            'subscription_id' => $response->json('id'),
        ]);
    }

    public function deleteExistingSubscription(): void
    {
        $subscription = StravaWebhookSubscription::latest()->first();

        if (! $subscription) {
            return;
        }

        $authorisationQueryString = http_build_query([
            'client_id' => config('strava.client_id'),
            'client_secret' => config('strava.client_secret'),
        ]);

        $response = app(StravaHttpClient::class)->delete(
            "push_subscriptions/{$subscription->strava_subscription_id}?{$authorisationQueryString}"
        );

        if ($response->failed()) {
            $response->throw();
        }

        $subscription->delete();
    }
}
