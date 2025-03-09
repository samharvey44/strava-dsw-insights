<?php

namespace App\Events;

class StravaActivityWebhookProcessedEvent extends Event
{
    public function __construct(
        public readonly int $stravaAthleteId,
        public readonly int $stravaActivityId,
    ) {
        //
    }
}
