<?php

namespace Tests\Unit\Models;

use App\Models\StravaActivity;
use App\Models\StravaConnection;
use App\Models\StravaRawActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StravaActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_by_user_scope(): void
    {
        $user = User::factory()->create();
        $stravaConnectionByUser = StravaConnection::factory()->create([
            'user_id' => $user->id,
        ]);
        $stravaActivityByUser = StravaActivity::factory()->create([
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => $stravaConnectionByUser->id,
            ])->id,
        ]);

        // Connection by a different user that should be ignored
        $stravaConnectionByDifferentUser = StravaConnection::factory()->create([
            'user_id' => User::factory()->create()->id,
        ]);
        StravaActivity::factory()->create([
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => $stravaConnectionByDifferentUser->id,
            ])->id,
        ]);

        $this->assertEquals([$stravaActivityByUser->id], StravaActivity::byUser($user)->pluck('id')->toArray());

        $userWithoutConnection = User::factory()->create();

        $this->assertEmpty(StravaActivity::byUser($userWithoutConnection)->pluck('id')->toArray());
    }
}
