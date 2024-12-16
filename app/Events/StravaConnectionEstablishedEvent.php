<?php

namespace App\Events;

use App\Models\StravaConnection;

class StravaConnectionEstablishedEvent extends Event
{
    public function __construct(
        public readonly StravaConnection $stravaConnection,
        public readonly bool $isReconnection = false,
    ) {
        //
    }
}
