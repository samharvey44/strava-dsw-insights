<?php

namespace App\Jobs;

use App\Models\DswType;
use App\Models\StravaActivity;
use App\Models\StravaConnection;
use App\Services\Strava\Activities\StravaActivitiesService;
use App\Services\Strava\DSWAnalysis\StravaActivityDswAnalysisService;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalyzeStravaActivitiesBatchJob extends Job
{
    public function __construct(public StravaConnection $stravaConnection, public int $limit, public int $offset)
    {
        //
    }

    public function handle(): void
    {
        $analysisService = app(StravaActivityDswAnalysisService::class);
        $activitiesService = app(StravaActivitiesService::class);

        $activitiesForBatch = StravaActivity::byUser($this->stravaConnection->user)
            ->whereDoesntHave('dswAnalysis')
            ->with([
                'rawActivity' => function (BelongsTo $query) {
                    $query->select('id', 'strava_activity_id');
                },
            ])
            ->orderBy('strava_activities.id')
            ->limit($this->limit)
            ->offset($this->offset)
            ->get();
        $allDswTypes = DswType::all();

        $activitiesForBatch->each(function (StravaActivity $stravaActivity) use ($analysisService, $activitiesService, $allDswTypes) {
            if (! $analysisService->isReAnalysable($stravaActivity, $allDswTypes)) {
                return;
            }

            // Fetch the full activity from the Strava API, to ensure it's up to date and not just a summary.
            $activitiesService->fetchActivityByStravaId(
                $this->stravaConnection,
                $stravaActivity->rawActivity->strava_activity_id
            );
            // Perform the analysis on the now up-to-date activity.
            $analysisService->performAnalysis($stravaActivity->fresh());
        });
    }
}
