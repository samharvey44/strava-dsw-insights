<?php

namespace App\Http\Controllers\Strava\Activities;

use App\Http\Controllers\Controller;
use App\Http\Requests\Strava\Activities\AttachGearRequest;
use App\Http\Requests\Strava\Activities\DetachGearRequest;
use App\Http\Requests\Strava\Activities\GearModalContentsRequest;
use App\Models\Gear;
use App\Models\StravaActivity;
use App\Services\Gear\GearService;
use Illuminate\Contracts\View\View;

class StravaActivityController extends Controller
{
    public function gearModalContents(GearModalContentsRequest $request, StravaActivity $stravaActivity): View
    {
        $stravaActivity->load('gears');

        return view('pages.home.partials.activity_gear_modal_contents', [
            'gears' => app(GearService::class)->getUserGear(auth()->id()),
            'activity' => $stravaActivity,
        ]);
    }

    public function attachGear(AttachGearRequest $request, StravaActivity $stravaActivity, Gear $gear): void
    {
        $stravaActivity->gears()->syncWithoutDetaching($gear);

        session()->flash('success', 'Gear attached successfully!');
    }

    public function detachGear(DetachGearRequest $request, StravaActivity $stravaActivity, Gear $gear): void
    {
        $stravaActivity->gears()->detach($gear);

        session()->flash('success', 'Gear detached successfully!');
    }
}
