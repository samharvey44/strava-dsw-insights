<?php

namespace App\Http\Integrations\Strava;

use App\Http\Integrations\AbstractHttpClient;
use App\Models\User;
use Http;
use Illuminate\Http\Client\PendingRequest;

class StravaHttpClient extends AbstractHttpClient
{
    protected function getBaseUri(): string
    {
        return 'https://www.strava.com/api/v3';
    }

    protected function getPendingRequest(?User $forUser): PendingRequest
    {
        $http = Http::asJson()->acceptJson();

        $accessToken = $forUser?->stravaConnection?->access_token;

        if ($accessToken) {
            $accessToken = decrypt($accessToken);

            $http = $http->withToken($accessToken);
        }

        return $http;
    }
}
