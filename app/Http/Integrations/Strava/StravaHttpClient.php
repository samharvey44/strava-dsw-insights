<?php

namespace App\Http\Integrations\Strava;

use App\Http\Integrations\AbstractHttpClient;
use App\Models\User;
use Http;
use Illuminate\Http\Client\PendingRequest;

class StravaHttpClient extends AbstractHttpClient
{
    public function __construct(private readonly User $forUser)
    {
        //
    }

    public function getBaseUri(): string
    {
        return 'https://www.strava.com/api/v3';
    }

    public function getPendingRequest(): PendingRequest
    {
        $http = Http::asJson()->acceptJson();

        $accessToken = $this->forUser->stravaConnection?->access_token;

        if ($accessToken) {
            $accessToken = decrypt($accessToken);

            $http = $http->withToken($accessToken);
        }

        return $http;
    }
}
