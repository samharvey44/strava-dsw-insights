<?php

namespace App\Services\Strava;

use App\Models\StravaConnection;
use App\Services\Strava\Auth\StravaAuthorisationService;

trait WithStravaAuthorisation
{
    public function withStravaAuthorisation(StravaConnection $stravaConnection, callable $callback): mixed
    {
        $accessTokenIsExpired = now()->addMinute()->getTimestamp() >= $stravaConnection->access_token_expiry;

        if ($accessTokenIsExpired) {
            $successfullyRefreshed = app(StravaAuthorisationService::class)->refreshAccessToken($stravaConnection);

            if (! $successfullyRefreshed) {
                throw new StravaAuthorisationFailedException($stravaConnection);
            }
        }

        return $callback($stravaConnection);
    }
}
