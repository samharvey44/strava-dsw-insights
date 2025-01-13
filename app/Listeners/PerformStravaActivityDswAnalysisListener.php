<?php

namespace App\Listeners;

use App\Events\StravaActivityReadyForDswAnalysisEvent;
use App\Models\StravaActivity;
use App\Services\Strava\DSWAnalysis\StravaActivityDswAnalysisService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;

class PerformStravaActivityDswAnalysisListener implements ShouldQueue
{
    public function handle(StravaActivityReadyForDswAnalysisEvent $event): void
    {
        $stravaActivity = StravaActivity::whereHas('rawActivity', function (Builder $query) use ($event) {
            $query->whereRelation('stravaConnection', 'athlete_id', $event->stravaAthleteId)
                ->where('strava_activity_id', $event->stravaActivityId);
        })->first();

        if (is_null($stravaActivity)) {
            return;
        }

        app(StravaActivityDswAnalysisService::class)->performAnalysis($stravaActivity);
    }
}
