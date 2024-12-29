<?php

namespace App\Services\Strava\Activities;

use App\Http\Integrations\Strava\StravaHttpClient;
use App\Models\StravaActivity;
use App\Models\StravaConnection;
use App\Models\StravaRawActivity;
use App\Services\Strava\WithStravaAuthorisation;
use Carbon\Carbon;
use DB;

class StravaActivitiesService
{
    use WithStravaAuthorisation;

    public function purgeActivities(StravaConnection $stravaConnection): void
    {
        StravaRawActivity::where('strava_connection_id', $stravaConnection->id)->delete();
    }

    public function fetchActivities(StravaConnection $stravaConnection): void
    {
        $this->withStravaAuthorisation($stravaConnection, function (StravaConnection $stravaConnection) {
            $page = 1;
            $perPage = 50;

            DB::transaction(function () use ($stravaConnection, $page, $perPage) {
                while (true) {
                    $response = app(StravaHttpClient::class)->get(
                        'athlete/activities',
                        [
                            'page' => $page,
                            'per_page' => $perPage,
                        ],
                        forUser: $stravaConnection->user,
                    );

                    if ($response->failed()) {
                        $response->throw();
                    }

                    $responseJson = $response->json();

                    if (empty($responseJson)) {
                        break;
                    }

                    $activitiesAlreadyStoredFromPage = StravaRawActivity::select('strava_activity_id')
                        ->where('strava_connection_id', $stravaConnection->id)
                        ->whereIn('strava_activity_id', array_column($responseJson, 'id'))
                        ->get();
                    $activitiesToStore = array_filter(
                        $responseJson,
                        fn ($activity) => $activitiesAlreadyStoredFromPage->doesntContain('strava_activity_id', $activity['id'])
                            && $activity['sport_type'] === 'Run'
                    );

                    foreach ($activitiesToStore as $activityToStore) {
                        $this->storeActivity($stravaConnection, $activityToStore);
                    }

                    $page++;
                }
            });
        });
    }

    private function storeActivity(StravaConnection $stravaConnection, array $activityData): void
    {
        $rawActivity = StravaRawActivity::create([
            'strava_connection_id' => $stravaConnection->id,
            'strava_activity_id' => $activityData['id'],
            'data' => $activityData,
        ]);

        StravaActivity::create([
            'strava_raw_activity_id' => $rawActivity->id,
            'name' => $activityData['name'],
            'description' => $activityData['description'] ?? null,
            'distance_meters' => $activityData['distance'],
            'moving_time_seconds' => $activityData['moving_time'],
            'elapsed_time_seconds' => $activityData['elapsed_time'],
            'elevation_gain_meters' => $activityData['total_elevation_gain'],
            'started_at' => Carbon::parse($activityData['start_date']),
            'timezone' => explode(' ', $activityData['timezone'])[1],
            'summary_polyline' => ($activityData['map']['summary_polyline'] ?? null) ?: null,
            'average_speed_meters_per_second' => $activityData['average_speed'],
            'max_speed_meters_per_second' => $activityData['max_speed'],
            'average_heartrate' => $activityData['average_heartrate'] ?? null,
            'max_heartrate' => $activityData['max_heartrate'] ?? null,
            'average_watts' => $activityData['average_watts'] ?? null,
            'max_watts' => $activityData['max_watts'] ?? null,
        ]);
    }
}
