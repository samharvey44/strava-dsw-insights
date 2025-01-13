<?php

namespace Tests\Unit\Listeners;

use App\Events\StravaActivityReadyForDswAnalysisEvent;
use App\Listeners\PerformStravaActivityDswAnalysisListener;
use App\Models\StravaActivity;
use App\Models\StravaConnection;
use App\Models\StravaRawActivity;
use App\Models\User;
use App\Services\Strava\DSWAnalysis\StravaActivityDswAnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PerformStravaActivityDswAnalysisListenerTest extends TestCase
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

        $mockedDswAnalysisService = Mockery::mock(StravaActivityDswAnalysisService::class);
        $mockedDswAnalysisService->shouldReceive('performAnalysis')->once()->with(
            Mockery::on(fn ($stravaActivityArg) => $stravaActivityArg->is($stravaActivity))
        );
        app()->instance(StravaActivityDswAnalysisService::class, $mockedDswAnalysisService);

        $event = app(StravaActivityReadyForDswAnalysisEvent::class, [
            'stravaAthleteId' => 123,
            'stravaActivityId' => 456,
        ]);

        $listener = app(PerformStravaActivityDswAnalysisListener::class);
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

        $mockedDswAnalysisService = Mockery::mock(StravaActivityDswAnalysisService::class);
        $mockedDswAnalysisService->shouldNotReceive('performAnalysis');
        app()->instance(StravaActivityDswAnalysisService::class, $mockedDswAnalysisService);

        $event = app(StravaActivityReadyForDswAnalysisEvent::class, [
            'stravaAthleteId' => 456,
            'stravaActivityId' => 456,
        ]);

        $listener = app(PerformStravaActivityDswAnalysisListener::class);
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

        $mockedDswAnalysisService = Mockery::mock(StravaActivityDswAnalysisService::class);
        $mockedDswAnalysisService->shouldNotReceive('performAnalysis');
        app()->instance(StravaActivityDswAnalysisService::class, $mockedDswAnalysisService);

        $event = app(StravaActivityReadyForDswAnalysisEvent::class, [
            'stravaAthleteId' => 123,
            'stravaActivityId' => 123,
        ]);

        $listener = app(PerformStravaActivityDswAnalysisListener::class);
        $listener->handle($event);
    }
}
