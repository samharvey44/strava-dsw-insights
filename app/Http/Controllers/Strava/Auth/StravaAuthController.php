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

        session()->put(StravaAuthorisationService::AUTHORISATION_STATE_KEY, $state);

        return redirect(app(StravaAuthorisationService::class)->generateAuthorisationLink($state));
    }

    public function redirect(Request $request): RedirectResponse
    {
        $accessDenied = $request->query('error') === 'access_denied';
        $codeQueryParamMissing = is_null($request->query('code'));
        $stateMissingOrInvalid = ! $request->query(StravaAuthorisationService::AUTHORISATION_STATE_KEY)
            || $request->query(StravaAuthorisationService::AUTHORISATION_STATE_KEY) !== session()->get(StravaAuthorisationService::AUTHORISATION_STATE_KEY);

        // We've validated our session state, so can remove it now.
        session()->forget(StravaAuthorisationService::AUTHORISATION_STATE_KEY);

        if ($accessDenied || $codeQueryParamMissing || $stateMissingOrInvalid) {
            return redirect()->signedRoute('strava-auth.unsuccessful');
        }

        $connection = app(StravaAuthorisationService::class)->performTokenExchange(
            auth()->user(),
            $request->query('code')
        );

        if (is_null($connection)) {
            return redirect()->signedRoute('strava-auth.unsuccessful');
        }

        return redirect()->signedRoute('strava-auth.successful');
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
