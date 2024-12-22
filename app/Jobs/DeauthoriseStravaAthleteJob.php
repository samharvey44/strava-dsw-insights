<?php

namespace App\Jobs;

use App\Models\StravaConnection;

class DeauthoriseStravaAthleteJob extends Job
{
    public function __construct(public int $stravaAthleteId)
    {
        //
    }

    public function handle(): void
    {
        StravaConnection::where('athlete_id', $this->stravaAthleteId)->update([
            'active' => false,
        ]);
    }
}
