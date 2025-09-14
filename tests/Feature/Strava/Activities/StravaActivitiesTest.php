<?php

namespace Tests\Feature\Strava\Activities;

use App\Models\Gear;
use App\Models\StravaActivity;
use App\Models\StravaConnection;
use App\Models\StravaRawActivity;
use App\Models\User;
use App\Services\Gear\GearService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class StravaActivitiesTest extends TestCase
{
    use RefreshDatabase;

    public function test_strava_activity_gear_modal_contents(): void
    {
        $this->actingAs($user = User::factory()->create());

        $gear = Gear::factory(3)->create([
            'user_id' => $user->id,
        ]);
        $stravaActivity = StravaActivity::factory()->create([
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => StravaConnection::factory()->create([
                    'user_id' => $user->id,
                ])->id,
            ])->id,
        ]);

        $mockedGearService = Mockery::mock(GearService::class);
        $mockedGearService->shouldReceive('getUserGear')->with($user->id)->andReturn($gear);
        app()->instance(GearService::class, $mockedGearService);

        $response = $this->get(route('activities.gear.modal-contents', $stravaActivity));

        $response->assertViewIs('pages.home.partials.activity_gear_modal_contents');
        $response->assertViewHas('gears', $gear);
        $response->assertViewHas('activity', $stravaActivity);
    }

    public function test_strava_activity_attach_gear(): void
    {
        $this->actingAs($user = User::factory()->create());

        $gear = Gear::factory()->create([
            'user_id' => $user->id,
        ]);

        $stravaActivity = StravaActivity::factory()->create([
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => StravaConnection::factory()->create([
                    'user_id' => $user->id,
                ])->id,
            ])->id,
        ]);

        $response = $this->post(route('activities.gear.attach', [$stravaActivity, $gear]));

        $response->assertSessionHas('success', 'Gear attached successfully!');

        $this->assertDatabaseHas('strava_activity_gear', [
            'strava_activity_id' => $stravaActivity->id,
            'gear_id' => $gear->id,
        ]);
    }

    public function test_strava_activity_detach_gear(): void
    {
        $this->actingAs($user = User::factory()->create());

        $gear = Gear::factory()->create([
            'user_id' => $user->id,
        ]);

        $stravaActivity = StravaActivity::factory()->create([
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => StravaConnection::factory()->create([
                    'user_id' => $user->id,
                ])->id,
            ])->id,
        ]);

        $stravaActivity->gears()->attach($gear);

        $response = $this->delete(route('activities.gear.detach', [$stravaActivity, $gear]));

        $response->assertSessionHas('success', 'Gear detached successfully!');

        $this->assertDatabaseMissing('strava_activity_gear', [
            'strava_activity_id' => $stravaActivity->id,
            'gear_id' => $gear->id,
        ]);
    }
}
