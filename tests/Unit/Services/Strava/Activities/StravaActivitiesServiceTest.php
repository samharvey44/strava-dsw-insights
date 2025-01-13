<?php

namespace Tests\Unit\Services\Strava\Activities;

use App\Models\StravaActivity;
use App\Models\StravaConnection;
use App\Models\StravaRawActivity;
use App\Models\User;
use App\Services\Strava\Activities\StravaActivitiesService;
use Carbon\Carbon;
use Http;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Mockery;
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
        $this->freezeSecond();

        $stravaConnection = StravaConnection::factory()->create([
            'user_id' => User::factory()->create()->id,
            'access_token' => encrypt('test-access-token'),
            'access_token_expiry' => now()->addSeconds(61)->getTimestamp(),
        ]);

        $pageOneResponses = [];
        $pageTwoResponses = [];

        foreach (range(1, 50) as $i) {
            $pageOneResponses[] = $this->generateStravaActivityJson();
        }
        foreach (range(1, 50) as $i) {
            $pageTwoResponses[] = $this->generateStravaActivityJson();
        }

        Http::fake([
            'https://www.strava.com/api/v3/athlete/activities?page=1&per_page=50' => Http::response($pageOneResponses),
            'https://www.strava.com/api/v3/athlete/activities?page=2&per_page=50' => Http::response($pageTwoResponses),
            'https://www.strava.com/api/v3/athlete/activities?page=3&per_page=50' => Http::response([]),
        ]);

        app(StravaActivitiesService::class)->fetchActivities($stravaConnection);

        foreach (array_merge($pageOneResponses, $pageTwoResponses) as $response) {
            $this->assertDatabaseHas('strava_raw_activities', [
                'strava_connection_id' => $stravaConnection->id,
                'strava_activity_id' => $response['id'],
            ]);

            $matchingRawActivity = StravaRawActivity::where('strava_activity_id', $response['id'])->first();

            $this->assertJsonStringEqualsJsonString(
                json_encode($response),
                json_encode($matchingRawActivity->data),
            );

            $this->assertDatabaseHas('strava_activities', [
                'strava_raw_activity_id' => $matchingRawActivity->id,
                'name' => $response['name'],
                'description' => $response['description'],
                'distance_meters' => $response['distance'],
                'moving_time_seconds' => $response['moving_time'],
                'elapsed_time_seconds' => $response['elapsed_time'],
                'elevation_gain_meters' => $response['total_elevation_gain'],
                'started_at' => Carbon::parse($response['start_date'])->format('Y-m-d H:i:s'),
                'timezone' => explode(' ', $response['timezone'])[1],
                'summary_polyline' => $response['map']['summary_polyline'],
                'average_speed_meters_per_second' => $response['average_speed'],
                'max_speed_meters_per_second' => $response['max_speed'],
                'average_heartrate' => $response['average_heartrate'],
                'max_heartrate' => $response['max_heartrate'],
                'average_watts' => $response['average_watts'],
                'max_watts' => $response['max_watts'],
            ]);
        }

        $this->assertDatabaseCount('strava_raw_activities', 100);

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://www.strava.com/api/v3/athlete/activities?page=1&per_page=50'
                && $request->hasHeader('Authorization', 'Bearer test-access-token');
        });
        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://www.strava.com/api/v3/athlete/activities?page=2&per_page=50'
                && $request->hasHeader('Authorization', 'Bearer test-access-token');
        });
        // A third request should have been sent, since 50 (the per_page) activities were returned on the second page.
        // We wouldn't know there are no more activities to fetch until we receive a response with less than 50 activities.
        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://www.strava.com/api/v3/athlete/activities?page=3&per_page=50'
                && $request->hasHeader('Authorization', 'Bearer test-access-token');
        });
        Http::assertSentCount(3);
    }

    public function test_successful_fetch_activities_with_non_run_activities(): void
    {
        $this->freezeSecond();

        $stravaConnection = StravaConnection::factory()->create([
            'user_id' => User::factory()->create()->id,
            'access_token' => encrypt('test-access-token'),
            'access_token_expiry' => now()->addSeconds(61)->getTimestamp(),
        ]);

        $stravaActivityJson = $this->generateStravaActivityJson();
        $stravaActivityJson['sport_type'] = 'Ride';

        Http::fake([
            'https://www.strava.com/api/v3/athlete/activities?page=1&per_page=50' => Http::response([$stravaActivityJson]),
        ]);

        app(StravaActivitiesService::class)->fetchActivities($stravaConnection);

        // The response should be ignored, since the activity is not a run activity.
        $this->assertDatabaseMissing('strava_raw_activities', [
            'strava_connection_id' => $stravaConnection->id,
            'strava_activity_id' => $stravaActivityJson['id'],
        ]);
        $this->assertDatabaseCount('strava_raw_activities', 0);

        $this->assertDatabaseCount('strava_activities', 0);

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://www.strava.com/api/v3/athlete/activities?page=1&per_page=50'
                && $request->hasHeader('Authorization', 'Bearer test-access-token');
        });
        Http::assertSentCount(1);
    }

    public function test_successful_fetch_activities_with_duplicate_for_same_connection(): void
    {
        $this->freezeSecond();

        $stravaConnection = StravaConnection::factory()->create([
            'user_id' => User::factory()->create()->id,
            'access_token' => encrypt('test-access-token'),
            'access_token_expiry' => now()->addSeconds(61)->getTimestamp(),
        ]);

        $existingActivityJson = $this->generateStravaActivityJson();

        $existingRawActivity = StravaRawActivity::factory()->create([
            'strava_connection_id' => $stravaConnection->id,
            'strava_activity_id' => $existingActivityJson['id'],
            'data' => $existingActivityJson,
        ]);
        $existingActivity = StravaActivity::factory()->create([
            'strava_raw_activity_id' => $existingRawActivity->id,
        ]);

        Http::fake([
            'https://www.strava.com/api/v3/athlete/activities?page=1&per_page=50' => Http::response([$existingActivityJson]),
        ]);

        app(StravaActivitiesService::class)->fetchActivities($stravaConnection);

        // The response should be ignored, since the activity is already stored.
        // Assert that we only have the existing activity stored.
        $this->assertDatabaseHas('strava_raw_activities', [
            'id' => $existingRawActivity->id,
            'strava_connection_id' => $stravaConnection->id,
            'strava_activity_id' => $existingActivityJson['id'],
        ]);
        $this->assertDatabaseHas('strava_activities', [
            'id' => $existingActivity->id,
            'strava_raw_activity_id' => $existingRawActivity->id,
        ]);

        $this->assertDatabaseCount('strava_raw_activities', 1);
        $this->assertDatabaseCount('strava_activities', 1);

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://www.strava.com/api/v3/athlete/activities?page=1&per_page=50'
                && $request->hasHeader('Authorization', 'Bearer test-access-token');
        });
        Http::assertSentCount(1);
    }

    public function test_successful_fetch_activities_with_duplicate_for_different_connection(): void
    {
        $this->freezeSecond();

        $stravaConnection = StravaConnection::factory()->create([
            'user_id' => User::factory()->create()->id,
            'access_token' => encrypt('test-access-token'),
            'access_token_expiry' => now()->addSeconds(61)->getTimestamp(),
        ]);
        $otherStravaConnection = StravaConnection::factory()->create([
            'user_id' => User::factory()->create()->id,
            'access_token' => encrypt('test-access-token'),
            'access_token_expiry' => now()->addSeconds(61)->getTimestamp(),
        ]);

        $existingActivityJson = $this->generateStravaActivityJson();

        $existingRawActivity = StravaRawActivity::factory()->create([
            'strava_connection_id' => $stravaConnection->id,
            'strava_activity_id' => $existingActivityJson['id'],
            'data' => $existingActivityJson,
        ]);
        $existingActivity = StravaActivity::factory()->create([
            'strava_raw_activity_id' => $existingRawActivity->id,
        ]);

        Http::fake([
            'https://www.strava.com/api/v3/athlete/activities?page=1&per_page=50' => Http::response([$existingActivityJson]),
        ]);

        app(StravaActivitiesService::class)->fetchActivities($otherStravaConnection);

        // The response should be stored, since the activity is not already stored for this connection.
        // Assert that we have the activity stored for both connections.
        $this->assertDatabaseHas('strava_raw_activities', [
            'id' => $existingRawActivity->id,
            'strava_connection_id' => $stravaConnection->id,
            'strava_activity_id' => $existingActivityJson['id'],
        ]);
        $this->assertDatabaseHas('strava_activities', [
            'id' => $existingActivity->id,
            'strava_raw_activity_id' => $existingRawActivity->id,
        ]);

        $this->assertDatabaseHas('strava_raw_activities', [
            'strava_connection_id' => $otherStravaConnection->id,
            'strava_activity_id' => $existingActivityJson['id'],
        ]);
        $this->assertDatabaseHas('strava_activities', [
            'strava_raw_activity_id' => StravaRawActivity::where('strava_connection_id', $otherStravaConnection->id)
                ->where('strava_activity_id', $existingActivityJson['id'])
                ->first()
                ->id,
        ]);

        $this->assertDatabaseCount('strava_raw_activities', 2);
        $this->assertDatabaseCount('strava_activities', 2);

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://www.strava.com/api/v3/athlete/activities?page=1&per_page=50'
                && $request->hasHeader('Authorization', 'Bearer test-access-token');
        });
        Http::assertSentCount(1);
    }

    public function test_failed_fetch_activities(): void
    {
        $this->freezeSecond();

        $stravaConnection = StravaConnection::factory()->create([
            'user_id' => User::factory()->create()->id,
            'access_token' => encrypt('test-access-token'),
            'access_token_expiry' => now()->addSeconds(61)->getTimestamp(),
        ]);

        Http::fake([
            'https://www.strava.com/api/v3/athlete/activities?page=1&per_page=50' => Http::response([], 500),
        ]);

        $this->expectException(RequestException::class);

        app(StravaActivitiesService::class)->fetchActivities($stravaConnection);

        $this->assertDatabaseCount('strava_raw_activities', 0);
        $this->assertDatabaseCount('strava_activities', 0);

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://www.strava.com/api/v3/athlete/activities?page=1&per_page=50'
                && $request->hasHeader('Authorization', 'Bearer test-access-token');
        });
        Http::assertSentCount(1);
    }

    public function test_fetch_activities_more_than_1000_results(): void
    {
        $this->freezeSecond();

        $stravaConnection = StravaConnection::factory()->create([
            'user_id' => User::factory()->create()->id,
            'access_token' => encrypt('test-access-token'),
            'access_token_expiry' => now()->addSeconds(61)->getTimestamp(),
        ]);

        foreach (range(1, 21) as $page) {
            Http::fake([
                "https://www.strava.com/api/v3/athlete/activities?page={$page}&per_page=50" => Http::response(
                    array_fill(0, 50, $this->generateStravaActivityJson()),
                ),
            ]);
        }

        app(StravaActivitiesService::class)->fetchActivities($stravaConnection);

        $this->assertDatabaseCount('strava_raw_activities', 1000);
        $this->assertDatabaseCount('strava_activities', 1000);

        foreach (range(1, 20) as $page) {
            Http::assertSent(function (Request $request) use ($page) {
                return $request->url() === "https://www.strava.com/api/v3/athlete/activities?page={$page}&per_page=50"
                    && $request->hasHeader('Authorization', 'Bearer test-access-token');
            });
        }

        Http::assertNotSent(function (Request $request) {
            return $request->url() === 'https://www.strava.com/api/v3/athlete/activities?page=21&per_page=50';
        });

        Http::assertSentCount(20);
    }

    public function test_successful_fetch_run_activity_by_strava_id_not_existing(): void
    {
        $this->freezeSecond();

        $stravaConnection = StravaConnection::factory()->create([
            'user_id' => User::factory()->create()->id,
            'access_token' => encrypt('test-access-token'),
            'access_token_expiry' => now()->addSeconds(61)->getTimestamp(),
        ]);
        $otherStravaConnection = StravaConnection::factory()->create([
            'user_id' => User::factory()->create()->id,
            'access_token' => encrypt('test-access-token'),
            'access_token_expiry' => now()->addSeconds(61)->getTimestamp(),
        ]);

        $activityJson = $this->generateStravaActivityJson();

        $otherStravaConnectionRawActivity = StravaRawActivity::factory()->create([
            'strava_connection_id' => $otherStravaConnection->id,
            'strava_activity_id' => $activityJson['id'],
            'data' => $activityJson,
        ]);
        StravaActivity::factory()->create([
            'strava_raw_activity_id' => $otherStravaConnectionRawActivity->id,
        ]);

        Http::fake([
            'https://www.strava.com/api/v3/activities/'.$activityJson['id'] => Http::response($activityJson),
        ]);

        app(StravaActivitiesService::class)->fetchActivityByStravaId($stravaConnection, $activityJson['id']);

        // Activity should be stored for the connection that requested it,
        // since it doesn't matter if it's already stored for another connection.
        $this->assertDatabaseHas('strava_raw_activities', [
            'strava_connection_id' => $stravaConnection->id,
            'strava_activity_id' => $activityJson['id'],
        ]);

        $matchingRawActivity = StravaRawActivity::where('strava_connection_id', $stravaConnection->id)
            ->where('strava_activity_id', $activityJson['id'])
            ->first();

        $this->assertJsonStringEqualsJsonString(
            json_encode($activityJson),
            json_encode($matchingRawActivity->data),
        );

        $this->assertDatabaseHas('strava_activities', [
            'strava_raw_activity_id' => $matchingRawActivity->id,
            'name' => $activityJson['name'],
            'description' => $activityJson['description'],
            'distance_meters' => $activityJson['distance'],
            'moving_time_seconds' => $activityJson['moving_time'],
            'elapsed_time_seconds' => $activityJson['elapsed_time'],
            'elevation_gain_meters' => $activityJson['total_elevation_gain'],
            'started_at' => Carbon::parse($activityJson['start_date'])->format('Y-m-d H:i:s'),
            'timezone' => explode(' ', $activityJson['timezone'])[1],
            'summary_polyline' => $activityJson['map']['summary_polyline'],
            'average_speed_meters_per_second' => $activityJson['average_speed'],
            'max_speed_meters_per_second' => $activityJson['max_speed'],
            'average_heartrate' => $activityJson['average_heartrate'],
            'max_heartrate' => $activityJson['max_heartrate'],
            'average_watts' => $activityJson['average_watts'],
            'max_watts' => $activityJson['max_watts'],
        ]);

        Http::assertSent(function (Request $request) use ($activityJson) {
            return $request->url() === 'https://www.strava.com/api/v3/activities/'.$activityJson['id']
                && $request->hasHeader('Authorization', 'Bearer test-access-token');
        });
        Http::assertSentCount(1);
    }

    public function test_successful_fetch_non_run_activity_by_strava_id_not_existing(): void
    {
        $this->freezeSecond();

        $stravaConnection = StravaConnection::factory()->create([
            'user_id' => User::factory()->create()->id,
            'access_token' => encrypt('test-access-token'),
            'access_token_expiry' => now()->addSeconds(61)->getTimestamp(),
        ]);
        $otherStravaConnection = StravaConnection::factory()->create([
            'user_id' => User::factory()->create()->id,
            'access_token' => encrypt('test-access-token'),
            'access_token_expiry' => now()->addSeconds(61)->getTimestamp(),
        ]);

        $activityJson = $this->generateStravaActivityJson();
        $activityJson['sport_type'] = 'Ride';

        $otherStravaConnectionRawActivity = StravaRawActivity::factory()->create([
            'strava_connection_id' => $otherStravaConnection->id,
            'strava_activity_id' => $activityJson['id'],
            'data' => $activityJson,
        ]);
        StravaActivity::factory()->create([
            'strava_raw_activity_id' => $otherStravaConnectionRawActivity->id,
        ]);

        Http::fake([
            'https://www.strava.com/api/v3/activities/'.$activityJson['id'] => Http::response($activityJson),
        ]);

        app(StravaActivitiesService::class)->fetchActivityByStravaId($stravaConnection, $activityJson['id']);

        // Activity should not be stored for the connection that requested it,
        // since it's not a run activity, but it should be retained for the connection that already has it stored.
        $this->assertDatabaseMissing('strava_raw_activities', [
            'strava_connection_id' => $stravaConnection->id,
            'strava_activity_id' => $activityJson['id'],
        ]);

        $this->assertDatabaseHas('strava_raw_activities', [
            'strava_connection_id' => $otherStravaConnection->id,
            'strava_activity_id' => $activityJson['id'],
        ]);
        $this->assertDatabaseHas('strava_activities', [
            'strava_raw_activity_id' => $otherStravaConnectionRawActivity->id,
        ]);

        $this->assertDatabaseCount('strava_activities', 1);

        Http::assertSent(function (Request $request) use ($activityJson) {
            return $request->url() === 'https://www.strava.com/api/v3/activities/'.$activityJson['id']
                && $request->hasHeader('Authorization', 'Bearer test-access-token');
        });
        Http::assertSentCount(1);
    }

    public function test_successful_fetch_run_activity_by_strava_id_existing(): void
    {
        $this->freezeSecond();

        $stravaConnection = StravaConnection::factory()->create([
            'user_id' => User::factory()->create()->id,
            'access_token' => encrypt('test-access-token'),
            'access_token_expiry' => now()->addSeconds(61)->getTimestamp(),
        ]);
        $otherStravaConnection = StravaConnection::factory()->create([
            'user_id' => User::factory()->create()->id,
            'access_token' => encrypt('test-access-token'),
            'access_token_expiry' => now()->addSeconds(61)->getTimestamp(),
        ]);

        $oldActivityJson = $this->generateStravaActivityJson();
        $newActivityJson = $this->generateStravaActivityJson();
        $oldActivityJson['id'] = $newActivityJson['id'];

        $existingRawActivity = StravaRawActivity::factory()->create([
            'strava_connection_id' => $stravaConnection->id,
            'strava_activity_id' => $oldActivityJson['id'],
            'data' => $oldActivityJson,
        ]);
        $existingActivity = StravaActivity::factory()->create([
            'strava_raw_activity_id' => $existingRawActivity->id,
        ]);

        $otherStravaConnectionRawActivity = StravaRawActivity::factory()->create([
            'strava_connection_id' => $otherStravaConnection->id,
            'strava_activity_id' => $oldActivityJson['id'],
            'data' => $oldActivityJson,
        ]);
        StravaActivity::factory()->create([
            'strava_raw_activity_id' => $otherStravaConnectionRawActivity->id,
        ]);

        Http::fake([
            'https://www.strava.com/api/v3/activities/'.$oldActivityJson['id'] => Http::response($newActivityJson),
        ]);

        app(StravaActivitiesService::class)->fetchActivityByStravaId($stravaConnection, $oldActivityJson['id']);

        // Activity should be updated for the connection that requested it,
        // but not for the connection that already has it stored.
        $this->assertDatabaseHas('strava_raw_activities', [
            'id' => $existingRawActivity->id,
            'strava_connection_id' => $stravaConnection->id,
            'strava_activity_id' => $oldActivityJson['id'],
        ]);

        $matchingRawActivity = StravaRawActivity::where('strava_activity_id', $oldActivityJson['id'])->first();

        $this->assertJsonStringEqualsJsonString(
            json_encode($newActivityJson),
            json_encode($matchingRawActivity->data),
        );

        $this->assertDatabaseHas('strava_activities', [
            'id' => $existingActivity->id,
            'strava_raw_activity_id' => $matchingRawActivity->id,
            'name' => $newActivityJson['name'],
            'description' => $newActivityJson['description'],
            'distance_meters' => $newActivityJson['distance'],
            'moving_time_seconds' => $newActivityJson['moving_time'],
            'elapsed_time_seconds' => $newActivityJson['elapsed_time'],
            'elevation_gain_meters' => $newActivityJson['total_elevation_gain'],
            'started_at' => Carbon::parse($newActivityJson['start_date'])->format('Y-m-d H:i:s'),
            'timezone' => explode(' ', $newActivityJson['timezone'])[1],
            'summary_polyline' => $newActivityJson['map']['summary_polyline'],
            'average_speed_meters_per_second' => $newActivityJson['average_speed'],
            'max_speed_meters_per_second' => $newActivityJson['max_speed'],
            'average_heartrate' => $newActivityJson['average_heartrate'],
            'max_heartrate' => $newActivityJson['max_heartrate'],
            'average_watts' => $newActivityJson['average_watts'],
            'max_watts' => $newActivityJson['max_watts'],
        ]);

        $this->assertDatabaseHas('strava_raw_activities', [
            'id' => $otherStravaConnectionRawActivity->id,
            'strava_connection_id' => $otherStravaConnection->id,
            'strava_activity_id' => $oldActivityJson['id'],
        ]);

        $this->assertJsonStringEqualsJsonString(
            json_encode($oldActivityJson),
            json_encode($otherStravaConnectionRawActivity->refresh()->data),
        );

        Http::assertSent(function (Request $request) use ($oldActivityJson) {
            return $request->url() === 'https://www.strava.com/api/v3/activities/'.$oldActivityJson['id']
                && $request->hasHeader('Authorization', 'Bearer test-access-token');
        });
        Http::assertSentCount(1);
    }

    public function test_successful_fetch_non_run_activity_by_strava_id_existing(): void
    {
        $this->freezeSecond();

        $stravaConnection = StravaConnection::factory()->create([
            'user_id' => User::factory()->create()->id,
            'access_token' => encrypt('test-access-token'),
            'access_token_expiry' => now()->addSeconds(61)->getTimestamp(),
        ]);
        $otherStravaConnection = StravaConnection::factory()->create([
            'user_id' => User::factory()->create()->id,
            'access_token' => encrypt('test-access-token'),
            'access_token_expiry' => now()->addSeconds(61)->getTimestamp(),
        ]);

        $oldActivityJson = $this->generateStravaActivityJson();
        $newActivityJson = $this->generateStravaActivityJson();
        $oldActivityJson['id'] = $newActivityJson['id'];
        $newActivityJson['sport_type'] = 'Ride';

        $mockedStravaActivitiesService = Mockery::mock(StravaActivitiesService::class)->makePartial();
        $mockedStravaActivitiesService->shouldReceive('deleteStoredActivityByStravaId')
            ->once()
            ->with(
                Mockery::on(fn ($stravaConnectionArg) => $stravaConnectionArg->is($stravaConnection)),
                $oldActivityJson['id'],
            );
        app()->instance(StravaActivitiesService::class, $mockedStravaActivitiesService);

        $existingRawActivity = StravaRawActivity::factory()->create([
            'strava_connection_id' => $stravaConnection->id,
            'strava_activity_id' => $oldActivityJson['id'],
            'data' => $oldActivityJson,
        ]);
        StravaActivity::factory()->create([
            'strava_raw_activity_id' => $existingRawActivity->id,
        ]);

        $otherStravaConnectionRawActivity = StravaRawActivity::factory()->create([
            'strava_connection_id' => $otherStravaConnection->id,
            'strava_activity_id' => $oldActivityJson['id'],
            'data' => $oldActivityJson,
        ]);
        StravaActivity::factory()->create([
            'strava_raw_activity_id' => $otherStravaConnectionRawActivity->id,
        ]);

        Http::fake([
            'https://www.strava.com/api/v3/activities/'.$oldActivityJson['id'] => Http::response($newActivityJson),
        ]);

        app(StravaActivitiesService::class)->fetchActivityByStravaId($stravaConnection, $oldActivityJson['id']);

        // Activity should be removed for the connection that requested it,
        // since it's not a run activity, but it should be retained for the connection that already has it stored.
        $this->assertDatabaseHas('strava_raw_activities', [
            'strava_connection_id' => $otherStravaConnection->id,
            'strava_activity_id' => $oldActivityJson['id'],
        ]);

        http::assertSent(function (Request $request) use ($oldActivityJson) {
            return $request->url() === 'https://www.strava.com/api/v3/activities/'.$oldActivityJson['id']
                && $request->hasHeader('Authorization', 'Bearer test-access-token');
        });
        Http::assertSentCount(1);
    }

    public function test_failed_fetch_activity_by_strava_id(): void
    {
        $this->freezeSecond();

        $stravaConnection = StravaConnection::factory()->create([
            'user_id' => User::factory()->create()->id,
            'access_token' => encrypt('test-access-token'),
            'access_token_expiry' => now()->addSeconds(61)->getTimestamp(),
        ]);

        $activityJson = $this->generateStravaActivityJson();

        Http::fake([
            'https://www.strava.com/api/v3/activities/'.$activityJson['id'] => Http::response([], 500),
        ]);

        $this->expectException(RequestException::class);

        app(StravaActivitiesService::class)->fetchActivityByStravaId($stravaConnection, $activityJson['id']);

        $this->assertDatabaseCount('strava_raw_activities', 0);
        $this->assertDatabaseCount('strava_activities', 0);

        Http::assertSent(function (Request $request) use ($activityJson) {
            return $request->url() === 'https://www.strava.com/api/v3/activities/'.$activityJson['id']
                && $request->hasHeader('Authorization', 'Bearer test-access-token');
        });
        Http::assertSentCount(1);
    }

    public function test_delete_stored_activity_by_strava_id(): void
    {
        $stravaConnection = StravaConnection::factory()->create([
            'user_id' => User::factory()->create()->id,
        ]);
        $otherStravaConnection = StravaConnection::factory()->create([
            'user_id' => User::factory()->create()->id,
        ]);

        $rawActivityToDelete = StravaRawActivity::factory()->create([
            'strava_connection_id' => $stravaConnection->id,
            'strava_activity_id' => fake()->unique()->randomNumber(),
        ]);
        $otherRawActivity = StravaRawActivity::factory()->create([
            'strava_connection_id' => $otherStravaConnection->id,
            'strava_activity_id' => fake()->unique()->randomNumber(),
        ]);

        app(StravaActivitiesService::class)->deleteStoredActivityByStravaId(
            $stravaConnection,
            $rawActivityToDelete->strava_activity_id
        );

        $this->assertDatabaseMissing('strava_raw_activities', [
            'id' => $rawActivityToDelete->id,
        ]);
        $this->assertDatabaseHas('strava_raw_activities', [
            'id' => $otherRawActivity->id,
        ]);
    }

    private function generateStravaActivityJson(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(),
            'sport_type' => 'Run',
            'name' => fake()->name(),
            'description' => fake()->sentence(),
            'distance' => fake()->randomFloat(2, 0, 100),
            'moving_time' => fake()->randomNumber(3),
            'elapsed_time' => fake()->randomNumber(3),
            'total_elevation_gain' => fake()->randomFloat(2, 0, 100),
            'start_date' => fake()->dateTime()->format('Y-m-d\TH:i:s\Z'),
            'timezone' => sprintf('(GMT%s%s:00) %s', fake()->randomElement(['+0', '-0']), fake()->randomNumber(2), fake()->timezone()),
            'map' => [
                'summary_polyline' => fake()->sentence(),
            ],
            'average_speed' => fake()->randomFloat(2, 0, 100),
            'max_speed' => fake()->randomFloat(2, 0, 100),
            'average_heartrate' => fake()->randomNumber(3),
            'max_heartrate' => fake()->randomNumber(3),
            'average_watts' => fake()->randomNumber(3),
            'max_watts' => fake()->randomNumber(3),
        ];
    }
}
