<?php

namespace Tests\Feature\Home;

use App\Models\User;
use App\Services\Strava\Auth\StravaAuthorisationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_view_home_page(): void
    {
        $stravaAuthorisationServiceMock = Mockery::mock(StravaAuthorisationService::class);
        $stravaAuthorisationServiceMock->shouldReceive('generateAuthorisationLink')
            ->andReturn('some-url');
        $this->app->instance(StravaAuthorisationService::class, $stravaAuthorisationServiceMock);

        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $response->assertViewIs('pages.home.index');
        $response->assertViewHas('stravaAuthorisationLink', 'some-url');
    }
}
