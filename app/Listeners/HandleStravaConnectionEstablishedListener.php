<?php

namespace App\Listeners;

use App\Events\StravaConnectionEstablishedEvent;
use App\Jobs\AnalyzeStravaActivityJob;
use App\Models\StravaActivity;
use App\Services\Strava\Activities\StravaActivitiesService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class HandleStravaConnectionEstablishedListener implements ShouldQueue
{
    public function handle(StravaConnectionEstablishedEvent $event): void
    {
        $stravaActivitiesService = app(StravaActivitiesService::class);

        if (! $event->isReconnection) {
            $stravaActivitiesService->purgeActivities($event->stravaConnection);
        }

        $stravaActivitiesService->fetchActivities($event->stravaConnection);

        $delaySeconds = 0;

        StravaActivity::byUser($event->stravaConnection->user)
            ->where(fn (Builder $query) => $query->whereDoesntHave('dswAnalysis'))
            ->limit(1000)
            ->chunkById(100, function (Collection $activities) use (&$delaySeconds) {
                $activities->each(function (StravaActivity $activity) use (&$delaySeconds) {
                    $delayUntil = now()->addSeconds($delaySeconds);

                    AnalyzeStravaActivityJob::dispatch($activity)->delay($delayUntil);

                    $delaySeconds += 5;
                });
            });
    }
}
