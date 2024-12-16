<?php

namespace Tests\Unit\Services\Strava;

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
        config(['strava.redirect_uri' => 'some-uri']);

        $user = User::factory()->create();

        $service = app(StravaAuthorisationService::class);

        $link = $service->generateAuthorisationLink($user);

        $expectedQueryString = http_build_query([
            'client_id' => config('strava.client_id'),
            'redirect_uri' => "some-uri?user={$user->id}",
            'response_type' => 'code',
            'approval_prompt' => 'auto',
            'scope' => 'read,activity:read_all',
        ]);

        $this->assertEquals(
            sprintf('https://www.strava.com/oauth/authorize?%s', $expectedQueryString),
            $link,
        );
    }

    public function test_strava_authorisation_link_generation_with_redirect_uri_not_set_in_config(): void
    {
        config(['strava.redirect_uri' => null]);

        $user = User::factory()->create();

        $service = app(StravaAuthorisationService::class);

        $link = $service->generateAuthorisationLink($user);

        $expectedQueryString = http_build_query([
            'client_id' => config('strava.client_id'),
            'redirect_uri' => route('strava-auth.redirect')."?user={$user->id}",
            'response_type' => 'code',
            'approval_prompt' => 'auto',
            'scope' => 'read,activity:read_all',
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
}
