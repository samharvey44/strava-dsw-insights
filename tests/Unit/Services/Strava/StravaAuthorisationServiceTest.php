<?php

namespace Tests\Unit\Services\Strava;

use App\Models\User;
use App\Services\Strava\Auth\StravaAuthorisationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StravaAuthorisationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function testStravaAuthorisationLinkIsGeneratedCorrectlyWhenRedirectUriIsSetInConfig(): void
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

    public function testStravaAuthorisationLinkIsGeneratedCorrectlyWhenRedirectUriIsNotSetInConfig(): void
    {
        config(['strava.redirect_uri' => null]);

        $user = User::factory()->create();

        $service = app(StravaAuthorisationService::class);

        $link = $service->generateAuthorisationLink($user);

        $expectedQueryString = http_build_query([
            'client_id' => config('strava.client_id'),
            'redirect_uri' => route('strava.auth-redirect') . "?user={$user->id}",
            'response_type' => 'code',
            'approval_prompt' => 'auto',
            'scope' => 'read,activity:read_all',
        ]);

        $this->assertEquals(
            sprintf('https://www.strava.com/oauth/authorize?%s', $expectedQueryString),
            $link,
        );
    }
}
