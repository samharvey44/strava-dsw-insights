<?php

namespace App\Listeners;

use App\Events\StravaActivityWebhookProcessedEvent;
use App\Models\StravaActivity;
use App\Services\Gear\Reminders\GearRemindersService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;

class AttachGearAndTriggerRemindersForStravaActivityListener implements ShouldQueue
{
    public function handle(StravaActivityWebhookProcessedEvent $event): void
    {
        $stravaActivity = StravaActivity::whereHas('rawActivity', function (Builder $query) use ($event) {
            $query->whereRelation('stravaConnection', 'athlete_id', $event->stravaAthleteId)
                ->where('strava_activity_id', $event->stravaActivityId);
        })->first();

        if (is_null($stravaActivity)) {
            return;
        }

        app(GearRemindersService::class)->attachGearAndTriggerReminders($stravaActivity);
    }
}
