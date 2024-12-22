<?php

namespace App\Services\Strava\Auth;

use App\Events\StravaConnectionEstablishedEvent;
use App\Http\Integrations\Strava\StravaHttpClient;
use App\Models\StravaConnection;
use App\Models\User;

class StravaAuthorisationService
{
    public const string AUTHORISATION_STATE_SESSION_KEY = 'strava_authorisation_state';

    public function generateAuthorisationLink(string $state): string
    {
        $stravaAuthorisationQueryString = http_build_query([
            'client_id' => config('strava.client_id'),
            'redirect_uri' => config('strava.auth_redirect_uri') ?? route('strava.auth.redirect'),
            'response_type' => 'code',
            'approval_prompt' => 'auto',
            'scope' => 'read,activity:read_all',
            'state' => $state,
        ]);

        return sprintf(
            'https://www.strava.com/oauth/authorize?%s',
            $stravaAuthorisationQueryString
        );
    }

    public function performTokenExchange(User $user, string $code): ?StravaConnection
    {
        $response = app(StravaHttpClient::class)->post(
            'oauth/token',
            [
                'client_id' => config('strava.client_id'),
                'client_secret' => config('strava.client_secret'),
                'code' => $code,
                'grant_type' => 'authorization_code',
            ]
        );

        if ($response->failed()) {
            return null;
        }

        $responseJson = $response->json();

        $previousStravaConnection = $user->stravaConnection()->first();
        $previousAthleteId = $previousStravaConnection?->athlete_id;

        $newStravaConnection = $previousStravaConnection ?? StravaConnection::make([
            'user_id' => $user->id,
        ]);

        $newStravaConnection->fill([
            'athlete_id' => $responseJson['athlete']['id'],
            'access_token' => encrypt($responseJson['access_token']),
            'refresh_token' => encrypt($responseJson['refresh_token']),
            'access_token_expiry' => now()->addSeconds($responseJson['expires_in'])->getTimestamp(),
            'active' => true,
        ]);

        $newStravaConnection->save();

        StravaConnectionEstablishedEvent::dispatch($newStravaConnection, $previousAthleteId === $newStravaConnection->athlete_id);

        return $newStravaConnection;
    }
}
