<?php

namespace App\Http\Controllers\Strava;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Strava\Auth\StravaAuthorisationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StravaAuthController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        $accessDenied = $request->query('error') === 'access_denied';
        $codeQueryParamMissing = is_null($request->query('code'));
        $user = $request->query('user') ? User::find($request->query('user')) : null;

        if ($accessDenied || $codeQueryParamMissing || is_null($user)) {
            return redirect()->route('strava-auth.unsuccessful');
        }

        $connection = app(StravaAuthorisationService::class)->performTokenExchange(
            $user,
            $request->query('code')
        );

        if (is_null($connection)) {
            return redirect()->route('strava-auth.unsuccessful');
        }

        return redirect()->route('strava-auth.successful');
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
