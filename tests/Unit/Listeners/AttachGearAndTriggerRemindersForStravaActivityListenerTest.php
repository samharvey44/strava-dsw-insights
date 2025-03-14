<?php

namespace Tests\Unit\Listeners;

use App\Events\StravaActivityWebhookProcessedEvent;
use App\Listeners\AttachGearAndTriggerRemindersForStravaActivityListener;
use App\Models\StravaActivity;
use App\Models\StravaConnection;
use App\Models\StravaRawActivity;
use App\Models\User;
use App\Services\Gear\Reminders\GearRemindersService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AttachGearAndTriggerRemindersForStravaActivityListenerTest extends TestCase
{
    use RefreshDatabase;

    public function test_listener_with_valid_activity(): void
    {
        $stravaActivity = StravaActivity::factory()->create([
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => StravaConnection::factory()->create([
                    'user_id' => User::factory()->create()->id,
                    'athlete_id' => 123,
                ])->id,
                'strava_activity_id' => 456,
            ])->id,
        ]);

        $mockedGearRemindersService = Mockery::mock(GearRemindersService::class);
        $mockedGearRemindersService->shouldReceive('attachGearAndTriggerReminders')->once()->with(
            Mockery::on(fn ($stravaActivityArg) => $stravaActivityArg->is($stravaActivity))
        );
        app()->instance(GearRemindersService::class, $mockedGearRemindersService);

        $event = app(StravaActivityWebhookProcessedEvent::class, [
            'stravaAthleteId' => 123,
            'stravaActivityId' => 456,
        ]);

        $listener = app(AttachGearAndTriggerRemindersForStravaActivityListener::class);
        $listener->handle($event);
    }

    public function test_listener_with_invalid_activity_by_athlete_id(): void
    {
        StravaActivity::factory()->create([
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => StravaConnection::factory()->create([
                    'user_id' => User::factory()->create()->id,
                    'athlete_id' => 123,
                ])->id,
                'strava_activity_id' => 456,
            ])->id,
        ]);

        $mockedGearRemindersService = Mockery::mock(GearRemindersService::class);
        $mockedGearRemindersService->shouldNotReceive('attachGearAndTriggerReminders');
        app()->instance(GearRemindersService::class, $mockedGearRemindersService);

        $event = app(StravaActivityWebhookProcessedEvent::class, [
            'stravaAthleteId' => 456,
            'stravaActivityId' => 456,
        ]);

        $listener = app(AttachGearAndTriggerRemindersForStravaActivityListener::class);
        $listener->handle($event);
    }

    public function test_listener_with_invalid_activity_by_activity_id(): void
    {
        StravaActivity::factory()->create([
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => StravaConnection::factory()->create([
                    'user_id' => User::factory()->create()->id,
                    'athlete_id' => 123,
                ])->id,
                'strava_activity_id' => 456,
            ])->id,
        ]);

        $mockedGearRemindersService = Mockery::mock(GearRemindersService::class);
        $mockedGearRemindersService->shouldNotReceive('attachGearAndTriggerReminders');
        app()->instance(GearRemindersService::class, $mockedGearRemindersService);

        $event = app(StravaActivityWebhookProcessedEvent::class, [
            'stravaAthleteId' => 123,
            'stravaActivityId' => 123,
        ]);

        $listener = app(AttachGearAndTriggerRemindersForStravaActivityListener::class);
        $listener->handle($event);
    }
}
