<?php

namespace Services\Strava;

use App\Models\StravaWebhookSubscription;
use App\Services\Strava\Webhooks\StravaWebhooksService;
use Http;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\RequestException;
use Tests\TestCase;

class StravaWebhooksServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_check_for_existing_webhook_with_existing_webhook(): void
    {
        config([
            'strava.client_id' => 'client-id',
            'strava.client_secret' => 'client-secret',
        ]);

        Http::fake([
            'https://www.strava.com/api/v3/push_subscriptions?client_id=client-id&client_secret=client-secret' => Http::response([
                [
                    'id' => 123,
                ],
            ], 200),
        ]);

        $result = app(StravaWebhooksService::class)->checkExistingSubscription();

        $this->assertEquals(123, $result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://www.strava.com/api/v3/push_subscriptions?client_id=client-id&client_secret=client-secret';
        });
    }

    public function test_successful_check_for_existing_webhook_with_no_existing_webhook(): void
    {
        config([
            'strava.client_id' => 'client-id',
            'strava.client_secret' => 'client-secret',
        ]);

        Http::fake([
            'https://www.strava.com/api/v3/push_subscriptions?client_id=client-id&client_secret=client-secret' => Http::response([], 200),
        ]);

        $result = app(StravaWebhooksService::class)->checkExistingSubscription();

        $this->assertEquals(null, $result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://www.strava.com/api/v3/push_subscriptions?client_id=client-id&client_secret=client-secret';
        });
    }

    public function test_unsuccessful_check_for_existing_webhook(): void
    {
        config([
            'strava.client_id' => 'client-id',
            'strava.client_secret' => 'client-secret',
        ]);

        $this->expectException(RequestException::class);

        Http::fake([
            'https://www.strava.com/api/v3/push_subscriptions?client_id=client-id&client_secret=client-secret' => Http::response([], 500),
        ]);

        app(StravaWebhooksService::class)->checkExistingSubscription();

        Http::assertSent(function ($request) {
            return $request->url() === 'https://www.strava.com/api/v3/push_subscriptions?client_id=client-id&client_secret=client-secret';
        });
    }

    public function test_successful_creation_and_storage_of_new_webhook_with_callback_uri_in_config(): void
    {
        config([
            'strava.client_id' => 'client-id',
            'strava.client_secret' => 'client-secret',
            'strava.webhook_callback_uri' => 'http://example.com/webhook',
            'strava.webhook_verify_token' => 'verify-token',
        ]);

        $originalWebhookSubscriptionId = StravaWebhookSubscription::factory()->create()->id;

        Http::fake([
            'https://www.strava.com/api/v3/push_subscriptions' => Http::response([
                'id' => 123,
            ], 200),
        ]);

        app(StravaWebhooksService::class)->createAndStoreNewSubscription();

        Http::assertSent(function ($request) {
            return $request->url() === 'https://www.strava.com/api/v3/push_subscriptions'
                && $request['client_id'] === 'client-id'
                && $request['client_secret'] === 'client-secret'
                && $request['callback_url'] === 'http://example.com/webhook'
                && $request['verify_token'] === 'verify-token';
        });

        $this->assertDatabaseCount('strava_webhook_subscriptions', 1);
        $this->assertDatabaseHas('strava_webhook_subscriptions', [
            'strava_subscription_id' => 123,
        ]);
        $this->assertDatabaseMissing('strava_webhook_subscriptions', [
            'id' => $originalWebhookSubscriptionId,
        ]);
    }

    public function test_successful_creation_and_storage_of_new_webhook_with_no_callback_uri_in_config(): void
    {
        config([
            'strava.client_id' => 'client-id',
            'strava.client_secret' => 'client-secret',
            'strava.webhook_callback_uri' => null,
            'strava.webhook_verify_token' => 'verify-token',
        ]);

        $originalWebhookSubscriptionId = StravaWebhookSubscription::factory()->create()->id;

        Http::fake([
            'https://www.strava.com/api/v3/push_subscriptions' => Http::response([
                'id' => 123,
            ], 200),
        ]);

        app(StravaWebhooksService::class)->createAndStoreNewSubscription();

        Http::assertSent(function ($request) {
            return $request->url() === 'https://www.strava.com/api/v3/push_subscriptions'
                && $request['client_id'] === 'client-id'
                && $request['client_secret'] === 'client-secret'
                && $request['callback_url'] === route('strava.webhook-updates')
                && $request['verify_token'] === 'verify-token';
        });

        $this->assertDatabaseCount('strava_webhook_subscriptions', 1);
        $this->assertDatabaseHas('strava_webhook_subscriptions', [
            'strava_subscription_id' => 123,
        ]);
        $this->assertDatabaseMissing('strava_webhook_subscriptions', [
            'id' => $originalWebhookSubscriptionId,
        ]);
    }

    public function test_unsuccessful_creation_and_storage_of_new_webhook(): void
    {
        config([
            'strava.client_id' => 'client-id',
            'strava.client_secret' => 'client-secret',
            'strava.webhook_callback_uri' => 'http://example.com/webhook',
            'strava.webhook_verify_token' => 'verify-token',
        ]);

        $originalWebhookSubscriptionId = StravaWebhookSubscription::factory()->create()->id;

        Http::fake([
            'https://www.strava.com/api/v3/push_subscriptions' => Http::response([], 500),
        ]);

        $this->expectException(RequestException::class);

        app(StravaWebhooksService::class)->createAndStoreNewSubscription();

        Http::assertSent(function ($request) {
            return $request->url() === 'https://www.strava.com/api/v3/push_subscriptions'
                && $request['client_id'] === 'client-id'
                && $request['client_secret'] === 'client-secret'
                && $request['callback_url'] === 'http://example.com/webhook'
                && $request['verify_token'] === 'verify-token';
        });

        $this->assertDatabaseCount('strava_webhook_subscriptions', 1);
        $this->assertDatabaseHas('strava_webhook_subscriptions', [
            'id' => $originalWebhookSubscriptionId,
        ]);
    }

    public function test_successful_deletion_of_existing_webhook(): void
    {
        config([
            'strava.client_id' => 'client-id',
            'strava.client_secret' => 'client-secret',
        ]);

        StravaWebhookSubscription::factory()->create(['strava_subscription_id' => 123]);

        Http::fake([
            'https://www.strava.com/api/v3/push_subscriptions/123?client_id=client-id&client_secret=client-secret' => Http::response([], 204),
        ]);

        app(StravaWebhooksService::class)->deleteExistingSubscription();

        Http::assertSent(function ($request) {
            return $request->url() === 'https://www.strava.com/api/v3/push_subscriptions/123?client_id=client-id&client_secret=client-secret';
        });

        $this->assertDatabaseCount('strava_webhook_subscriptions', 0);
    }

    public function test_unsuccessful_deletion_of_existing_webhook(): void
    {
        config([
            'strava.client_id' => 'client-id',
            'strava.client_secret' => 'client-secret',
        ]);

        StravaWebhookSubscription::factory()->create(['strava_subscription_id' => 123]);

        Http::fake([
            'https://www.strava.com/api/v3/push_subscriptions/123?client_id=client-id&client_secret=client-secret' => Http::response([], 500),
        ]);

        $this->expectException(RequestException::class);

        app(StravaWebhooksService::class)->deleteExistingSubscription();

        Http::assertSent(function ($request) {
            return $request->url() === 'https://www.strava.com/api/v3/push_subscriptions/123?client_id=client-id&client_secret=client-secret';
        });

        $this->assertDatabaseHas('strava_webhook_subscriptions', [
            'strava_subscription_id' => 123,
        ]);
    }

    public function test_deletion_of_non_existent_webhook(): void
    {
        config([
            'strava.client_id' => 'client-id',
            'strava.client_secret' => 'client-secret',
        ]);

        Http::fake([]);

        app(StravaWebhooksService::class)->deleteExistingSubscription();

        Http::assertNothingSent();
    }
}
