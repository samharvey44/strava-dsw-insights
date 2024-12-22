<?php

namespace App\Http\Controllers\Strava\Auth;

use App\Http\Controllers\Controller;
use App\Services\Strava\Auth\StravaAuthorisationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Str;

class StravaAuthController extends Controller
{
    public function initiateAuthorisation(): RedirectResponse
    {
        $state = Str::random(30);

        session()->put(StravaAuthorisationService::AUTHORISATION_STATE_SESSION_KEY, $state);

        return redirect(app(StravaAuthorisationService::class)->generateAuthorisationLink($state));
    }

    public function redirect(Request $request): RedirectResponse
    {
        $accessDenied = $request->query('error') === 'access_denied';
        $codeQueryParamMissing = is_null($request->query('code'));
        $stateQueryParamMissingOrInvalid = ! $request->query('state')
            || $request->query('state') !== session()->get(StravaAuthorisationService::AUTHORISATION_STATE_SESSION_KEY);

        // We've validated our session state, so can remove it now.
        session()->forget(StravaAuthorisationService::AUTHORISATION_STATE_SESSION_KEY);

        if ($accessDenied || $codeQueryParamMissing || $stateQueryParamMissingOrInvalid) {
            return redirect()->signedRoute('strava.auth.unsuccessful');
        }

        $connection = app(StravaAuthorisationService::class)->performTokenExchange(
            auth()->user(),
            $request->query('code')
        );

        if (is_null($connection)) {
            return redirect()->signedRoute('strava.auth.unsuccessful');
        }

        return redirect()->signedRoute('strava.auth.successful');
    }

    public function successful(): View
    {
        return view('pages.strava.auth.success');
    }

    public function unsuccessful(): View
    {
        return view('pages.strava.auth.unsuccessful');
    }
}
