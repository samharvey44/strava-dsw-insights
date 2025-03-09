<?php

namespace App\Http\Controllers\Gear\Reminders;

use App\Http\Controllers\Controller;
use App\Http\Requests\Gear\Reminders\DestroyGearReminderRequest;
use App\Http\Requests\Gear\Reminders\GearReminderModalContentsRequest;
use App\Http\Requests\Gear\Reminders\StoreGearReminderRequest;
use App\Http\Requests\Gear\Reminders\UpdateGearReminderRequest;
use App\Models\Gear;
use App\Models\GearReminder;
use App\Services\Gear\Reminders\GearRemindersService;
use Illuminate\Contracts\View\View;

class GearReminderController extends Controller
{
    public function modalContents(GearReminderModalContentsRequest $request, Gear $gear): View
    {
        $gear->load('reminders');

        return view('pages.gear.partials.gear_reminder_modal_contents', [
            'gearItem' => $gear,
        ]);
    }

    public function store(StoreGearReminderRequest $request, Gear $gear)
    {
        app(GearRemindersService::class)->store(
            $gear->id,
            $request->input('name'),
            $request->input('trigger_after_number_of_activities'),
            $request->input('current_number_of_activities')
        );

        session()->flash('success', 'Reminder created successfully!');
    }

    public function update(UpdateGearReminderRequest $request, Gear $gear, GearReminder $gearReminder)
    {
        app(GearRemindersService::class)->update(
            $gearReminder,
            $request->input('name'),
            $request->input('trigger_after_number_of_activities'),
            $request->input('current_number_of_activities')
        );

        session()->flash('success', 'Reminder updated successfully!');
    }

    public function destroy(DestroyGearReminderRequest $request, Gear $gear, GearReminder $gearReminder)
    {
        app(GearRemindersService::class)->destroy($gearReminder);

        session()->flash('success', 'Reminder deleted successfully!');
    }
}
