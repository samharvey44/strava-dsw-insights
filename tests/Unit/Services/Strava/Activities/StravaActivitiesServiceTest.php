<?php

namespace Tests\Unit\Services\Strava\Activities;

use App\Models\StravaConnection;
use App\Models\StravaRawActivity;
use App\Models\User;
use App\Services\Strava\Activities\StravaActivitiesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StravaActivitiesServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_purge_activities(): void
    {
        $purgingStravaConnection = StravaConnection::factory()->create([
            'user_id' => User::factory()->create()->id,
        ]);
        $notPurgingStravaConnection = StravaConnection::factory()->create([
            'user_id' => User::factory()->create()->id,
        ]);

        $purgingRawActivity = StravaRawActivity::factory()->create([
            'strava_connection_id' => $purgingStravaConnection->id,
        ]);
        $notPurgingRawActivity = StravaRawActivity::factory()->create([
            'strava_connection_id' => $notPurgingStravaConnection->id,
        ]);

        app(StravaActivitiesService::class)->purgeActivities($purgingStravaConnection);

        $this->assertDatabaseMissing('strava_raw_activities', [
            'id' => $purgingRawActivity->id,
        ]);
        $this->assertDatabaseHas('strava_raw_activities', [
            'id' => $notPurgingRawActivity->id,
        ]);
    }

    public function test_successful_fetch_activities(): void
    {
        $this->markTestIncomplete('TODO - implement test, ensure correct deduplication of existing activities');
    }

    public function test_failed_fetch_activities(): void
    {
        $this->markTestIncomplete('TODO - implement test, ensure exception is thrown');
    }
}
