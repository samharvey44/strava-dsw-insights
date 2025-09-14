<?php

namespace App\Services\Gear\Reminders;

use App\Mail\GearReminderNotificationMail;
use App\Models\Gear;
use App\Models\GearReminder;
use App\Models\StravaActivity;
use DB;
use Illuminate\Support\Facades\Mail;

class GearRemindersService
{
    public function store(
        string $gearId,
        string $name,
        int $triggerAfterNumberOfActivities,
        int $currentNumberOfActivities
    ): void {
        GearReminder::create([
            'gear_id' => $gearId,
            'name' => $name,
            'trigger_after_number_of_activities' => $triggerAfterNumberOfActivities,
            'current_number_of_activities' => $currentNumberOfActivities,
        ]);
    }

    public function update(
        GearReminder $gearReminder,
        string $name,
        int $triggerAfterNumberOfActivities,
        int $currentNumberOfActivities
    ): void {
        $gearReminder->update([
            'name' => $name,
            'trigger_after_number_of_activities' => $triggerAfterNumberOfActivities,
            'current_number_of_activities' => $currentNumberOfActivities,
        ]);
    }

    public function destroy(GearReminder $gearReminder): void
    {
        $gearReminder->delete();
    }

    public function attachGearAndTriggerReminders(StravaActivity $stravaActivity): void
    {
        $nonDecommissionedGearWithReminders = Gear::with('reminders')
            ->where(
                'user_id',
                $stravaActivity->rawActivity->stravaConnection->user_id
            )->where(function ($query) {
                $query->whereNull('decommissioned')
                    ->orWhereDate('decommissioned', '>', now()->toDateString());
            })
            ->where('auto_attach_to_activities', true)
            ->whereHas('reminders')
            ->get();

        DB::transaction(function () use ($stravaActivity, $nonDecommissionedGearWithReminders) {
            $stravaActivity->gears()->sync($nonDecommissionedGearWithReminders->pluck('id'));

            $nonDecommissionedGearWithReminders->flatMap(function (Gear $gear) {
                return $gear->reminders;
            })->each(function (GearReminder $gearReminder) use ($stravaActivity) {
                $updateGearReminderArray = [
                    'current_number_of_activities' => $newNumberOfTriggers = $gearReminder->current_number_of_activities + 1,
                ];

                if ($newNumberOfTriggers >= $gearReminder->trigger_after_number_of_activities) {
                    $updateGearReminderArray = [
                        'current_number_of_activities' => 0,
                        'last_triggered' => now(),
                    ];

                    $this->sendReminderNotification($gearReminder, $stravaActivity);
                }

                $gearReminder->update($updateGearReminderArray);
            });
        });
    }

    private function sendReminderNotification(GearReminder $gearReminder, StravaActivity $stravaActivity): void
    {
        Mail::to($stravaActivity->rawActivity->stravaConnection->user->email)
            ->queue(new GearReminderNotificationMail($gearReminder, $stravaActivity));
    }
}
