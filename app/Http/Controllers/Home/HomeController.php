<?php

namespace App\Http\Controllers\Home;

use App\Http\Controllers\Controller;
use App\Services\Strava\Auth\StravaAuthorisationService;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        return view('pages.home.index', [
            'stravaAuthorisationLink' => app(StravaAuthorisationService::class)->generateAuthorisationLink(auth()->user()),
        ]);
    }
}
