<?php

namespace Tests\Unit\Jobs;

use App\Jobs\DeauthoriseStravaAthleteJob;
use App\Models\StravaConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeauthoriseStravaAthleteJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_deauthorise_strava_athlete(): void
    {
        StravaConnection::factory()->create([
            'athlete_id' => 123,
            'user_id' => User::factory()->create()->id,
            'active' => true,
        ]);

        app(DeauthoriseStravaAthleteJob::class, ['stravaAthleteId' => 123])->handle();

        $this->assertDatabaseHas('strava_connections', [
            'athlete_id' => 123,
            'active' => false,
        ]);
    }
}
