<?php

namespace App\Http\Controllers\Strava;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Strava\Auth\StravaAuthorisationService;
use Illuminate\Http\Request;

class StravaAuthRedirectController extends Controller
{
    public function __invoke(Request $request): void
    {
        // TODO - error screen for 400 cases
        // TODO - success screen for 200 case
        // TODO - split Strava HTTP request classes by authenticated / unauthenticated

        abort_if($request->query('error') === 'access_denied', 400);
        abort_if(is_null($request->query('code')), 400);

        if (
            is_null($request->query('user'))
            || is_null($user = User::find($request->query('user')))
        ) {
            abort(400);
        }

        $connection = app(StravaAuthorisationService::class)->performTokenExchange(
            $user,
            $request->query('code')
        );

        abort_if(is_null($connection), 400);
    }
}
