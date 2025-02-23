<?php

namespace Tests\Unit\Services\Strava\Auth;

use App\Events\StravaConnectionEstablishedEvent;
use App\Models\StravaConnection;
use App\Models\User;
use App\Services\Strava\Auth\StravaAuthorisationService;
use Event;
use Http;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Tests\TestCase;

class StravaAuthorisationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_strava_authorisation_link_generation_with_redirect_uri_set_in_config(): void
    {
        config(['strava.client_id' => 'some-client-id']);
        config(['strava.auth_redirect_uri' => 'some-uri']);

        $service = app(StravaAuthorisationService::class);

        $link = $service->generateAuthorisationLink('state');

        $expectedQueryString = http_build_query([
            'client_id' => config('strava.client_id'),
            'redirect_uri' => 'some-uri',
            'response_type' => 'code',
            'approval_prompt' => 'auto',
            'scope' => 'read,activity:read_all',
            'state' => 'state',
        ]);

        $this->assertEquals(
            sprintf('https://www.strava.com/oauth/authorize?%s', $expectedQueryString),
            $link,
        );
    }

    public function test_strava_authorisation_link_generation_with_redirect_uri_not_set_in_config(): void
    {
        config(['strava.client_id' => 'some-client-id']);
        config(['strava.auth_redirect_uri' => null]);

        $service = app(StravaAuthorisationService::class);

        $link = $service->generateAuthorisationLink('state');

        $expectedQueryString = http_build_query([
            'client_id' => config('strava.client_id'),
            'redirect_uri' => route('strava.auth.redirect'),
            'response_type' => 'code',
            'approval_prompt' => 'auto',
            'scope' => 'read,activity:read_all',
            'state' => 'state',
        ]);

        $this->assertEquals(
            sprintf('https://www.strava.com/oauth/authorize?%s', $expectedQueryString),
            $link,
        );
    }

    public function test_successful_token_exchange_with_no_previous_connection(): void
    {
        config([
            'strava.client_id' => 'client-id',
            'strava.client_secret' => 'client-secret',
        ]);

        $user = User::factory()->create();

        $service = app(StravaAuthorisationService::class);

        Http::fake([
            'https://www.strava.com/api/v3/oauth/token' => Http::response([
                'athlete' => [
                    'id' => 123,
                ],
                'access_token' => 'access-token',
                'expires_in' => 123,
                'refresh_token' => 'refresh-token',
            ]),
        ]);
        Event::fake([
            StravaConnectionEstablishedEvent::class,
        ]);

        $this->freezeSecond();

        $connection = $service->performTokenExchange($user, 'code');

        $this->assertInstanceOf(StravaConnection::class, $connection);

        Event::assertDispatched(StravaConnectionEstablishedEvent::class, function (StravaConnectionEstablishedEvent $event) use ($connection) {
            return $event->stravaConnection->is($connection) && ! $event->isReconnection;
        });
        Http::assertSent(function (Request $request) {
            return $request->method() === 'POST'
                && $request['client_id'] === 'client-id'
                && $request['client_secret'] === 'client-secret'
                && $request['code'] === 'code'
                && $request['grant_type'] === 'authorization_code';
        });

        $this->assertDatabaseCount('strava_connections', 1);
        $this->assertDatabaseHas('strava_connections', [
            'id' => $connection->id,
            'user_id' => $user->id,
            'athlete_id' => 123,
            'access_token_expiry' => now()->addSeconds(123)->getTimestamp(),
            'active' => true,
        ]);
        $this->assertEquals('access-token', decrypt($connection->access_token));
        $this->assertEquals('refresh-token', decrypt($connection->refresh_token));
    }

    public function test_successful_token_exchange_with_previous_connection(): void
    {
        config([
            'strava.client_id' => 'client-id',
            'strava.client_secret' => 'client-secret',
        ]);

        $user = User::factory()->create();
        $previousConnection = StravaConnection::factory()->create([
            'user_id' => $user->id,
            'athlete_id' => 456,
            'access_token' => encrypt('not-access-token'),
            'refresh_token' => encrypt('not-refresh-token'),
            'access_token_expiry' => now()->subSeconds(123)->getTimestamp(),
            'active' => false,
        ]);

        $service = app(StravaAuthorisationService::class);

        Http::fake([
            'https://www.strava.com/api/v3/oauth/token' => Http::response([
                'athlete' => [
                    'id' => 123,
                ],
                'access_token' => 'access-token',
                'expires_in' => 123,
                'refresh_token' => 'refresh-token',
            ]),
        ]);
        Event::fake([
            StravaConnectionEstablishedEvent::class,
        ]);

        $this->freezeSecond();

        $connection = $service->performTokenExchange($user, 'code');

        $this->assertInstanceOf(StravaConnection::class, $connection);
        $this->assertTrue($connection->is($previousConnection));

        Event::assertDispatched(StravaConnectionEstablishedEvent::class, function (StravaConnectionEstablishedEvent $event) use ($connection) {
            return $event->stravaConnection->is($connection) && ! $event->isReconnection;
        });
        Http::assertSent(function (Request $request) {
            return $request->method() === 'POST'
                && $request['client_id'] === 'client-id'
                && $request['client_secret'] === 'client-secret'
                && $request['code'] === 'code'
                && $request['grant_type'] === 'authorization_code';
        });

        $this->assertDatabaseCount('strava_connections', 1);
        $this->assertDatabaseHas('strava_connections', [
            'id' => $connection->id,
            'user_id' => $user->id,
            'athlete_id' => 123,
            'access_token_expiry' => now()->addSeconds(123)->getTimestamp(),
            'active' => true,
        ]);
        $this->assertEquals('access-token', decrypt($connection->access_token));
        $this->assertEquals('refresh-token', decrypt($connection->refresh_token));
    }

    public function test_unsuccessful_token_exchange(): void
    {
        config([
            'strava.client_id' => 'client-id',
            'strava.client_secret' => 'client-secret',
        ]);

        $user = User::factory()->create();

        $service = app(StravaAuthorisationService::class);

        Http::fake([
            'https://www.strava.com/api/v3/oauth/token' => Http::response([], 400),
        ]);
        Event::fake([
            StravaConnectionEstablishedEvent::class,
        ]);

        $connection = $service->performTokenExchange($user, 'code');

        $this->assertNull($connection);

        Event::assertNotDispatched(StravaConnectionEstablishedEvent::class);
        Http::assertSent(function (Request $request) {
            return $request->method() === 'POST'
                && $request['client_id'] === 'client-id'
                && $request['client_secret'] === 'client-secret'
                && $request['code'] === 'code'
                && $request['grant_type'] === 'authorization_code';
        });

        $this->assertDatabaseCount('strava_connections', 0);
    }

    public function test_successful_refresh_access_token(): void
    {
        config([
            'strava.client_id' => 'client-id',
            'strava.client_secret' => 'client-secret',
        ]);

        $connection = StravaConnection::factory()->create([
            'user_id' => User::factory()->create()->id,
            'access_token' => encrypt('old-access-token'),
            'refresh_token' => encrypt('old-refresh-token'),
            'access_token_expiry' => now()->subSeconds(123)->getTimestamp(),
            'active' => true,
        ]);

        $service = app(StravaAuthorisationService::class);

        Http::fake([
            'https://www.strava.com/api/v3/oauth/token' => Http::response([
                'athlete' => [
                    'id' => 123,
                ],
                'access_token' => 'new-access-token',
                'expires_in' => 123,
                'refresh_token' => 'new-refresh-token',
            ]),
        ]);

        $this->freezeSecond();

        $result = $service->refreshAccessToken($connection);

        $this->assertTrue($result);

        Http::assertSent(function (Request $request) {
            return $request->method() === 'POST'
                && $request['client_id'] === 'client-id'
                && $request['client_secret'] === 'client-secret'
                && $request['refresh_token'] === 'old-refresh-token'
                && $request['grant_type'] === 'refresh_token';
        });

        $this->assertDatabaseCount('strava_connections', 1);
        $this->assertDatabaseHas('strava_connections', [
            'id' => $connection->id,
            'user_id' => $connection->user_id,
            'access_token_expiry' => now()->addSeconds(123)->getTimestamp(),
            'active' => true,
        ]);

        $this->assertEquals('new-access-token', decrypt($connection->access_token));
        $this->assertEquals('new-refresh-token', decrypt($connection->refresh_token));
    }

    public function test_unsuccessful_refresh_access_token(): void
    {
        config([
            'strava.client_id' => 'client-id',
            'strava.client_secret' => 'client-secret',
        ]);

        $this->freezeSecond();

        $connection = StravaConnection::factory()->create([
            'user_id' => User::factory()->create()->id,
            'access_token' => encrypt('old-access-token'),
            'refresh_token' => encrypt('old-refresh-token'),
            'access_token_expiry' => now()->subSeconds(123)->getTimestamp(),
            'active' => true,
        ]);

        $service = app(StravaAuthorisationService::class);

        Http::fake([
            'https://www.strava.com/api/v3/oauth/token' => Http::response([], 400),
        ]);

        $result = $service->refreshAccessToken($connection);
        $this->assertFalse($result);

        Http::assertSent(function (Request $request) {
            return $request->method() === 'POST'
                && $request['client_id'] === 'client-id'
                && $request['client_secret'] === 'client-secret'
                && $request['refresh_token'] === 'old-refresh-token'
                && $request['grant_type'] === 'refresh_token';
        });

        $this->assertDatabaseCount('strava_connections', 1);
        $this->assertDatabaseHas('strava_connections', [
            'id' => $connection->id,
            'user_id' => $connection->user_id,
            'access_token_expiry' => now()->subSeconds(123)->getTimestamp(),
            'active' => false,
        ]);

        $this->assertEquals('old-access-token', decrypt($connection->access_token));
        $this->assertEquals('old-refresh-token', decrypt($connection->refresh_token));
    }
}
