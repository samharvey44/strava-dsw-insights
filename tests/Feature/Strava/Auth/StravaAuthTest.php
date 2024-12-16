<?php

namespace Tests\Feature\Strava\Auth;

use App\Models\StravaConnection;
use App\Models\User;
use App\Services\Strava\Auth\StravaAuthorisationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;
use URL;

class StravaAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_strava_redirect(): void
    {
        $user = User::factory()->create();

        $mockedStravaAuthorisationService = Mockery::mock(StravaAuthorisationService::class);
        $mockedStravaAuthorisationService->shouldReceive('performTokenExchange')
            ->once()
            ->with(
                Mockery::on(fn (User $userArg) => $userArg->id === $user->id),
                'code'
            )
            ->andReturn(StravaConnection::factory()->create(['user_id' => $user->id]));
        app()->instance(StravaAuthorisationService::class, $mockedStravaAuthorisationService);

        $this->actingAs($user);

        $route = route('strava-auth.redirect', [
            'code' => 'code',
            'user' => $user->id,
        ]);

        $response = $this->get($route);

        $response->assertRedirectToSignedRoute('strava-auth.successful');
    }

    public function test_strava_redirect_with_access_denied_error(): void
    {
        $user = User::factory()->create();

        $mockedStravaAuthorisationService = Mockery::mock(StravaAuthorisationService::class);
        $mockedStravaAuthorisationService->shouldNotReceive('performTokenExchange');
        app()->instance(StravaAuthorisationService::class, $mockedStravaAuthorisationService);

        $this->actingAs($user);

        $route = route('strava-auth.redirect', [
            'error' => 'access_denied',
            'user' => $user->id,
        ]);

        $response = $this->get($route);

        $response->assertRedirectToSignedRoute('strava-auth.unsuccessful');
    }

    public function test_strava_redirect_with_missing_code_query_param(): void
    {
        $user = User::factory()->create();

        $mockedStravaAuthorisationService = Mockery::mock(StravaAuthorisationService::class);
        $mockedStravaAuthorisationService->shouldNotReceive('performTokenExchange');
        app()->instance(StravaAuthorisationService::class, $mockedStravaAuthorisationService);

        $this->actingAs($user);

        $route = route('strava-auth.redirect', [
            'user' => $user->id,
        ]);

        $response = $this->get($route);

        $response->assertRedirectToSignedRoute('strava-auth.unsuccessful');
    }

    public function test_strava_redirect_with_missing_user_query_param(): void
    {
        $user = User::factory()->create();

        $mockedStravaAuthorisationService = Mockery::mock(StravaAuthorisationService::class);
        $mockedStravaAuthorisationService->shouldNotReceive('performTokenExchange');
        app()->instance(StravaAuthorisationService::class, $mockedStravaAuthorisationService);

        $this->actingAs($user);

        $route = route('strava-auth.redirect', [
            'code' => 'code',
        ]);

        $response = $this->get($route);

        $response->assertRedirectToSignedRoute('strava-auth.unsuccessful');
    }

    public function test_strava_redirect_with_invalid_user(): void
    {
        $user = User::factory()->create();

        $mockedStravaAuthorisationService = Mockery::mock(StravaAuthorisationService::class);
        $mockedStravaAuthorisationService->shouldNotReceive('performTokenExchange');
        app()->instance(StravaAuthorisationService::class, $mockedStravaAuthorisationService);

        $this->actingAs($user);

        $route = route('strava-auth.redirect', [
            'code' => 'code',
            'user' => 999,
        ]);

        $response = $this->get($route);

        $response->assertRedirectToSignedRoute('strava-auth.unsuccessful');
    }

    public function test_strava_redirect_with_unsuccessful_token_exchange(): void
    {
        $user = User::factory()->create();

        $mockedStravaAuthorisationService = Mockery::mock(StravaAuthorisationService::class);
        $mockedStravaAuthorisationService->shouldReceive('performTokenExchange')
            ->once()
            ->with(
                Mockery::on(fn (User $userArg) => $userArg->id === $user->id),
                'code'
            )
            ->andReturnNull();
        app()->instance(StravaAuthorisationService::class, $mockedStravaAuthorisationService);

        $this->actingAs($user);

        $route = route('strava-auth.redirect', [
            'code' => 'code',
            'user' => $user->id,
        ]);

        $response = $this->get($route);

        $response->assertRedirectToSignedRoute('strava-auth.unsuccessful');
    }

    public function test_view_authorisation_successful(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->get(URL::signedRoute('strava-auth.successful'));

        $response->assertStatus(200);
        $response->assertViewIs('pages.strava.auth.success');
    }

    public function test_view_authorisation_unsuccessful(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->get(URL::signedRoute('strava-auth.unsuccessful'));

        $response->assertStatus(200);
        $response->assertViewIs('pages.strava.auth.unsuccessful');
    }
}
