<?php

namespace App\Http\Controllers\Home;

use App\Http\Controllers\Controller;
use App\Models\StravaActivity;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request): View
    {
        $activities = StravaActivity::byUser(auth()->user())
            ->with('dswAnalysis')
            ->latest('started_at')
            ->paginate(20);

        return view('pages.home.index', compact('activities'));
    }
}
