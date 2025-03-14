<?php

namespace Policies;

use App\Models\Gear;
use App\Models\StravaActivity;
use App\Models\StravaConnection;
use App\Models\StravaRawActivity;
use App\Models\User;
use App\Policies\StravaActivityPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StravaActivityPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_handle_gear_for_their_own_activity_gear_not_provided(): void
    {
        $user = User::factory()->create();
        $stravaActivity = StravaActivity::factory()->create([
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => StravaConnection::factory()->create([
                    'user_id' => $user->id,
                ])->id,
            ])->id,
        ]);

        $this->assertTrue(
            app(StravaActivityPolicy::class)->gear($user, $stravaActivity)
        );
    }

    public function test_user_can_handle_gear_for_their_own_activity_gear_provided(): void
    {
        $user = User::factory()->create();
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

        $this->assertTrue(
            app(StravaActivityPolicy::class)->gear($user, $stravaActivity, $gear)
        );
    }

    public function test_user_cannot_handle_gear_for_other_users_activity_gear_not_provided(): void
    {
        $user = User::factory()->create();
        $stravaActivity = StravaActivity::factory()->create([
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => StravaConnection::factory()->create([
                    'user_id' => User::factory()->create()->id,
                ])->id,
            ])->id,
        ]);

        $this->assertFalse(
            app(StravaActivityPolicy::class)->gear($user, $stravaActivity)
        );
    }

    public function test_user_cannot_handle_gear_for_other_users_activity_gear_provided(): void
    {
        $user = User::factory()->create();
        $gear = Gear::factory()->create([
            'user_id' => $user->id,
        ]);
        $stravaActivity = StravaActivity::factory()->create([
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => StravaConnection::factory()->create([
                    'user_id' => User::factory()->create()->id,
                ])->id,
            ])->id,
        ]);

        $this->assertFalse(
            app(StravaActivityPolicy::class)->gear($user, $stravaActivity, $gear)
        );
    }

    public function test_user_cannot_handle_gear_for_their_own_activity_gear_provided_but_not_theirs(): void
    {
        $user = User::factory()->create();
        $gear = Gear::factory()->create([
            'user_id' => User::factory()->create()->id,
        ]);
        $stravaActivity = StravaActivity::factory()->create([
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => StravaConnection::factory()->create([
                    'user_id' => $user->id,
                ])->id,
            ])->id,
        ]);

        $this->assertFalse(
            app(StravaActivityPolicy::class)->gear($user, $stravaActivity, $gear)
        );
    }

    public function test_user_cannot_handle_gear_for_other_users_activity_gear_provided_but_not_theirs(): void
    {
        $user = User::factory()->create();
        $gear = Gear::factory()->create([
            'user_id' => User::factory()->create()->id,
        ]);
        $stravaActivity = StravaActivity::factory()->create([
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => StravaConnection::factory()->create([
                    'user_id' => User::factory()->create()->id,
                ])->id,
            ])->id,
        ]);

        $this->assertFalse(
            app(StravaActivityPolicy::class)->gear($user, $stravaActivity, $gear)
        );
    }
}
