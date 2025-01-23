<?php

namespace Tests\Unit\Listeners;

use App\Events\StravaConnectionEstablishedEvent;
use App\Jobs\AnalyzeStravaActivityJob;
use App\Listeners\HandleStravaConnectionEstablishedListener;
use App\Models\DswType;
use App\Models\DswTypeGroup;
use App\Models\StravaActivity;
use App\Models\StravaActivityDswAnalysis;
use App\Models\StravaConnection;
use App\Models\StravaRawActivity;
use App\Models\User;
use App\Services\Strava\Activities\StravaActivitiesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Queue;
use Tests\TestCase;

class HandleStravaConnectionEstablishedListenerTest extends TestCase
{
    use RefreshDatabase;

    public function test_listener_with_reconnection_event(): void
    {
        Queue::fake();

        $stravaConnection = StravaConnection::factory()->create([
            'user_id' => User::factory()->create()->id,
        ]);

        $mockedStravaActivitiesService = Mockery::mock(StravaActivitiesService::class);
        $mockedStravaActivitiesService->shouldNotReceive('purgeActivities');
        $mockedStravaActivitiesService->shouldReceive('fetchActivities')
            ->with(Mockery::on(fn ($stravaConnectionArg) => $stravaConnectionArg->is($stravaConnection)))
            ->once();
        app()->instance(StravaActivitiesService::class, $mockedStravaActivitiesService);

        $event = app(StravaConnectionEstablishedEvent::class, [
            'stravaConnection' => $stravaConnection,
            'isReconnection' => true,
        ]);

        $listener = app(HandleStravaConnectionEstablishedListener::class);

        $listener->handle($event);
    }

    public function test_listener_with_non_reconnection_event(): void
    {
        Queue::fake();

        $stravaConnection = StravaConnection::factory()->create([
            'user_id' => User::factory()->create()->id,
        ]);

        $mockedStravaActivitiesService = Mockery::mock(StravaActivitiesService::class);
        $mockedStravaActivitiesService->shouldReceive('purgeActivities')
            ->with(Mockery::on(fn ($stravaConnectionArg) => $stravaConnectionArg->is($stravaConnection)))
            ->once();
        $mockedStravaActivitiesService->shouldReceive('fetchActivities')
            ->with(Mockery::on(fn ($stravaConnectionArg) => $stravaConnectionArg->is($stravaConnection)))
            ->once();
        app()->instance(StravaActivitiesService::class, $mockedStravaActivitiesService);

        $event = app(StravaConnectionEstablishedEvent::class, [
            'stravaConnection' => $stravaConnection,
            'isReconnection' => false,
        ]);

        $listener = app(HandleStravaConnectionEstablishedListener::class);

        $listener->handle($event);
    }

    public function test_listener_dispatches_analysis_for_unanalysed_activities(): void
    {
        Queue::fake();

        $this->freezeSecond();

        $stravaConnection = StravaConnection::factory()->create([
            'user_id' => User::factory()->create()->id,
        ]);

        $stravaActivity = StravaActivity::factory()->create([
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => $stravaConnection->id,
            ])->id,
        ]);

        $mockedStravaActivitiesService = Mockery::mock(StravaActivitiesService::class);
        $mockedStravaActivitiesService->shouldReceive('purgeActivities')
            ->with(Mockery::on(fn ($stravaConnectionArg) => $stravaConnectionArg->is($stravaConnection)))
            ->once();
        $mockedStravaActivitiesService->shouldReceive('fetchActivities')
            ->with(Mockery::on(fn ($stravaConnectionArg) => $stravaConnectionArg->is($stravaConnection)))
            ->once();
        app()->instance(StravaActivitiesService::class, $mockedStravaActivitiesService);

        $event = app(StravaConnectionEstablishedEvent::class, [
            'stravaConnection' => $stravaConnection,
            'isReconnection' => false,
        ]);

        $listener = app(HandleStravaConnectionEstablishedListener::class);

        $listener->handle($event);

        Queue::assertPushed(
            AnalyzeStravaActivityJob::class,
            function (AnalyzeStravaActivityJob $job) use ($stravaActivity) {
                return $job->stravaActivity->is($stravaActivity)
                    && $job->delay->toDateTimeString() === now()->toDateTimeString();
            }
        );
    }

    public function test_listener_does_not_dispatch_analysis_for_analysed_activities(): void
    {
        Queue::fake();

        $stravaConnection = StravaConnection::factory()->create([
            'user_id' => User::factory()->create()->id,
        ]);

        $analysedActivity = StravaActivity::factory()->create([
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => $stravaConnection->id,
            ])->id,
        ]);
        StravaActivityDswAnalysis::factory()->create([
            'strava_activity_id' => $analysedActivity->id,
            'dsw_type_id' => DswType::factory()->create([
                'dsw_type_group_id' => DswTypeGroup::factory()->create()->id,
            ])->id,
        ]);

        $mockedStravaActivitiesService = Mockery::mock(StravaActivitiesService::class);
        $mockedStravaActivitiesService->shouldReceive('purgeActivities')
            ->with(Mockery::on(fn ($stravaConnectionArg) => $stravaConnectionArg->is($stravaConnection)))
            ->once();
        $mockedStravaActivitiesService->shouldReceive('fetchActivities')
            ->with(Mockery::on(fn ($stravaConnectionArg) => $stravaConnectionArg->is($stravaConnection)))
            ->once();
        app()->instance(StravaActivitiesService::class, $mockedStravaActivitiesService);

        $event = app(StravaConnectionEstablishedEvent::class, [
            'stravaConnection' => $stravaConnection,
            'isReconnection' => false,
        ]);

        $listener = app(HandleStravaConnectionEstablishedListener::class);

        $listener->handle($event);

        Queue::assertNothingPushed();
    }

    public function test_listener_dispatches_analysis_for_only_1000_activities(): void
    {
        Queue::fake();

        $this->freezeSecond();

        $stravaConnection = StravaConnection::factory()->create([
            'user_id' => User::factory()->create()->id,
        ]);

        $createdActivities = [];

        foreach (range(1, 1000) as $i) {
            $rawActivity = StravaRawActivity::factory()->create([
                'strava_connection_id' => $stravaConnection->id,
            ]);

            $createdActivities[$i] = StravaActivity::factory()->create([
                'strava_raw_activity_id' => $rawActivity->id,
            ]);
        }

        $analysedActivity = StravaActivity::factory()->create([
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => $stravaConnection->id,
            ])->id,
        ]);
        StravaActivityDswAnalysis::factory()->create([
            'strava_activity_id' => $analysedActivity->id,
            'dsw_type_id' => DswType::factory()->create([
                'dsw_type_group_id' => DswTypeGroup::factory()->create()->id,
            ])->id,
        ]);

        $mockedStravaActivitiesService = Mockery::mock(StravaActivitiesService::class);
        $mockedStravaActivitiesService->shouldReceive('purgeActivities')
            ->with(Mockery::on(fn ($stravaConnectionArg) => $stravaConnectionArg->is($stravaConnection)))
            ->once();
        $mockedStravaActivitiesService->shouldReceive('fetchActivities')
            ->with(Mockery::on(fn ($stravaConnectionArg) => $stravaConnectionArg->is($stravaConnection)))
            ->once();
        app()->instance(StravaActivitiesService::class, $mockedStravaActivitiesService);

        $event = app(StravaConnectionEstablishedEvent::class, [
            'stravaConnection' => $stravaConnection,
            'isReconnection' => false,
        ]);

        $listener = app(HandleStravaConnectionEstablishedListener::class);

        $listener->handle($event);

        foreach (range(1, 1000) as $activityIndexToAnalyse) {
            Queue::assertPushed(
                AnalyzeStravaActivityJob::class,
                function (AnalyzeStravaActivityJob $job) use ($createdActivities, $activityIndexToAnalyse) {
                    return $job->stravaActivity->is($createdActivities[$activityIndexToAnalyse])
                        && $job->delay->toDateTimeString() === now()->addSeconds(($activityIndexToAnalyse - 1) * 5)->toDateTimeString();
                }
            );
        }

        Queue::assertNotPushed(
            AnalyzeStravaActivityJob::class,
            function (AnalyzeStravaActivityJob $job) use ($analysedActivity) {
                return $job->stravaActivity->is($analysedActivity);
            }
        );
    }
}
