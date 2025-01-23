<?php

namespace App\Jobs;

use App\Models\DswType;
use App\Models\StravaActivity;
use App\Services\Strava\Activities\StravaActivitiesService;
use App\Services\Strava\DSWAnalysis\StravaActivityDswAnalysisService;

class AnalyzeStravaActivityJob extends Job
{
    public function __construct(public StravaActivity $stravaActivity)
    {
        //
    }

    public function handle(): void
    {
        $analysisService = app(StravaActivityDswAnalysisService::class);
        $activitiesService = app(StravaActivitiesService::class);

        $allDswTypes = DswType::all();

        if (! $analysisService->isReAnalysable($this->stravaActivity, $allDswTypes)) {
            return;
        }

        // Fetch the full activity from the Strava API, to ensure it's up to date and not just a summary.
        $activitiesService->fetchActivityByStravaId(
            $this->stravaActivity->rawActivity->stravaConnection,
            $this->stravaActivity->rawActivity->strava_activity_id
        );
        // Perform the analysis on the now up-to-date activity.
        $analysisService->performAnalysis($this->stravaActivity->fresh());
    }
}
