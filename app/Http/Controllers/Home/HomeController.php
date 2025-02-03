<?php

namespace App\Http\Controllers\Home;

use App\Http\Controllers\Controller;
use App\Models\StravaActivity;
use App\Services\DswTypes\DswTypesService;
use App\Services\Home\HomeFilteringService;
use App\Services\Strava\DSWAnalysis\StravaActivityDswAnalysisScoringService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request): View
    {
        $activities = app(HomeFilteringService::class)->applyFiltersAndSort(
            StravaActivity::byUser(auth()->user())->with('dswAnalysis.dswType.typeGroup')->latest('started_at'),
            $request->all()['filters'] ?? []
        )->paginate(20);

        $scoreBands = auth()->user()->stravaConnection
            ? app(StravaActivityDswAnalysisScoringService::class)->getScoreBandsByType(
                auth()->user()->stravaConnection
            )
            : collect();

        $dswTypes = app(DswTypesService::class)->getAllTypes();

        $filtersApplied = is_array($request->get('filters'));

        return view(
            'pages.home.index',
            compact('activities', 'scoreBands', 'dswTypes', 'filtersApplied')
        );
    }
}
