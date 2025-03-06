<?php

namespace App\Http\Controllers\Gear;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGearRequest;
use App\Http\Requests\UpdateGearRequest;
use App\Models\Gear;
use App\Services\Gear\GearService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GearController extends Controller
{
    public function index(Request $request): View
    {
        $gear = Gear::query()
            ->where('user_id', auth()->id())
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('pages.gear.index', compact('gear'));
    }

    public function create(Request $request): View
    {
        return view('pages.gear.create');
    }

    public function store(StoreGearRequest $request): RedirectResponse
    {
        app(GearService::class)->store(
            $request->input('name'),
            $request->input('description'),
            $request->date('first_used'),
            $request->date('decommissioned'),
            $request->file('image')
        );

        return redirect()->route('gear')->with('success', 'Gear created successfully!');
    }

    public function edit(Gear $gear): View
    {
        return view('pages.gear.edit', compact('gear'));
    }

    public function update(UpdateGearRequest $request, Gear $gear): RedirectResponse
    {
        app(GearService::class)->update(
            $gear,
            $request->input('name'),
            $request->input('description'),
            $request->date('first_used'),
            $request->date('decommissioned'),
            $request->file('image')
        );

        return redirect()->route('gear')->with('success', 'Gear updated successfully!');
    }

    public function destroy(Gear $gear): RedirectResponse
    {
        app(GearService::class)->destroy($gear);

        return redirect()->route('gear')->with('success', 'Gear deleted successfully!');
    }
}
