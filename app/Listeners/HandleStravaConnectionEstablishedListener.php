<?php

namespace App\Listeners;

use App\Events\StravaConnectionEstablishedEvent;
use App\Jobs\AnalyzeStravaActivitiesBatchJob;
use App\Models\StravaActivity;
use App\Services\Strava\Activities\StravaActivitiesService;
use Illuminate\Contracts\Queue\ShouldQueue;

class HandleStravaConnectionEstablishedListener implements ShouldQueue
{
    private const int ANALYSIS_BATCH_SIZE = 10;

    public function handle(StravaConnectionEstablishedEvent $event): void
    {
        $stravaActivitiesService = app(StravaActivitiesService::class);

        if (! $event->isReconnection) {
            $stravaActivitiesService->purgeActivities($event->stravaConnection);
        }

        $stravaActivitiesService->fetchActivities($event->stravaConnection);

        $numberOfUnanalysedActivities = StravaActivity::byUser($event->stravaConnection->user)
            ->whereDoesntHave('dswAnalysis')
            ->count();
        $batchesToAnalyse = (int) ceil($numberOfUnanalysedActivities / self::ANALYSIS_BATCH_SIZE);

        if ($batchesToAnalyse === 0) {
            return;
        }

        foreach (range(0, min($batchesToAnalyse, 99)) as $analysisBatch) {
            AnalyzeStravaActivitiesBatchJob::dispatch(
                $event->stravaConnection,
                self::ANALYSIS_BATCH_SIZE,
                $analysisBatch * self::ANALYSIS_BATCH_SIZE,
            );
        }
    }
}
