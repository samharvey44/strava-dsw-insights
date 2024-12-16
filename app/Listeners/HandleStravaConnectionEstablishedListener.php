<?php

namespace App\Listeners;

use App\Events\StravaConnectionEstablishedEvent;
use App\Services\Strava\Activities\StravaActivitiesService;
use App\Services\Strava\Webhooks\StravaWebhooksService;
use Illuminate\Contracts\Queue\ShouldQueue;

class HandleStravaConnectionEstablishedListener implements ShouldQueue
{
    public function handle(StravaConnectionEstablishedEvent $event): void
    {
        $stravaActivitiesService = app(StravaActivitiesService::class);
        $stravaActivitiesService->purgeActivities($event->stravaConnection);
        $stravaActivitiesService->fetchActivities($event->stravaConnection);

        app(StravaWebhooksService::class)->subscribeToWebhooks($event->stravaConnection);
    }
}
