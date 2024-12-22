<?php

namespace Tests\Unit\Listeners;

use App\Events\StravaConnectionEstablishedEvent;
use App\Listeners\HandleStravaConnectionEstablishedListener;
use App\Models\StravaConnection;
use App\Models\User;
use App\Services\Strava\Activities\StravaActivitiesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class HandleStravaConnectionEstablishedListenerTest extends TestCase
{
    use RefreshDatabase;

    public function test_listener_with_reconnection_event(): void
    {
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
}
