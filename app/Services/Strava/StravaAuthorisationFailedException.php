<?php

namespace App\Services\Strava;

use App\Models\StravaConnection;
use Exception;

class StravaAuthorisationFailedException extends Exception
{
    public function __construct(StravaConnection $stravaConnection)
    {
        parent::__construct("Strava authorisation failed for connection ID: {$stravaConnection->id}");
    }
}
