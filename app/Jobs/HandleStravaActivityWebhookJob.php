<?php

namespace App\Jobs;

use App\Events\StravaActivityWebhookProcessedEvent;
use App\Models\StravaConnection;
use App\Services\Strava\Activities\StravaActivitiesService;
use App\Services\Strava\StravaWebhookAspectTypeEnum;

class HandleStravaActivityWebhookJob extends Job
{
    public function __construct(
        public StravaWebhookAspectTypeEnum $aspectType,
        public int $stravaAthleteId,
        public int $stravaActivityId,
    ) {
        //
    }

    public function handle(): void
    {
        $stravaConnection = StravaConnection::where('athlete_id', $this->stravaAthleteId)->first();

        if (! $stravaConnection) {
            return;
        }

        if ($this->aspectType === StravaWebhookAspectTypeEnum::DELETE) {
            app(StravaActivitiesService::class)->deleteStoredActivityByStravaId(
                $stravaConnection,
                $this->stravaActivityId
            );

            return;
        }

        app(StravaActivitiesService::class)->fetchActivityByStravaId(
            $stravaConnection,
            $this->stravaActivityId
        );

        StravaActivityWebhookProcessedEvent::dispatch(
            $this->stravaAthleteId,
            $this->stravaActivityId,
        );
    }
}
