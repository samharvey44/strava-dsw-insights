<?php

namespace Tests\Unit\Jobs;

use App\Events\StravaActivityWebhookProcessedEvent;
use App\Jobs\HandleStravaActivityWebhookJob;
use App\Models\StravaConnection;
use App\Models\User;
use App\Services\Strava\Activities\StravaActivitiesService;
use App\Services\Strava\StravaWebhookAspectTypeEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\TestCase;

class HandleStravaActivityWebhookJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_create_activity_webhook(): void
    {
        Event::fake();

        $stravaConnection = StravaConnection::factory()->create([
            'user_id' => User::factory()->create()->id,
            'athlete_id' => 123,
        ]);

        $mockedStravaActivitiesService = Mockery::mock(StravaActivitiesService::class);
        $mockedStravaActivitiesService->shouldReceive('fetchActivityByStravaId')
            ->once()
            ->with(
                Mockery::on(fn ($stravaConnectionArg) => $stravaConnectionArg->is($stravaConnection)),
                456
            );
        app()->instance(StravaActivitiesService::class, $mockedStravaActivitiesService);

        app(HandleStravaActivityWebhookJob::class, [
            'aspectType' => StravaWebhookAspectTypeEnum::CREATE,
            'stravaAthleteId' => $stravaConnection->athlete_id,
            'stravaActivityId' => 456,
        ])->handle();

        Event::assertDispatched(StravaActivityWebhookProcessedEvent::class, function (StravaActivityWebhookProcessedEvent $event) use ($stravaConnection) {
            return $event->stravaAthleteId === $stravaConnection->athlete_id
                && $event->stravaActivityId === 456;
        });
    }

    public function test_handle_update_activity_webhook(): void
    {
        Event::fake();

        $stravaConnection = StravaConnection::factory()->create([
            'user_id' => User::factory()->create()->id,
            'athlete_id' => 123,
        ]);

        $mockedStravaActivitiesService = Mockery::mock(StravaActivitiesService::class);
        $mockedStravaActivitiesService->shouldReceive('fetchActivityByStravaId')
            ->once()
            ->with(
                Mockery::on(fn ($stravaConnectionArg) => $stravaConnectionArg->is($stravaConnection)),
                456
            );
        app()->instance(StravaActivitiesService::class, $mockedStravaActivitiesService);

        app(HandleStravaActivityWebhookJob::class, [
            'aspectType' => StravaWebhookAspectTypeEnum::UPDATE,
            'stravaAthleteId' => $stravaConnection->athlete_id,
            'stravaActivityId' => 456,
        ])->handle();

        Event::assertDispatched(StravaActivityWebhookProcessedEvent::class, function (StravaActivityWebhookProcessedEvent $event) use ($stravaConnection) {
            return $event->stravaAthleteId === $stravaConnection->athlete_id
                && $event->stravaActivityId === 456;
        });
    }

    public function test_handle_delete_activity_webhook(): void
    {
        Event::fake();

        $stravaConnection = StravaConnection::factory()->create([
            'user_id' => User::factory()->create()->id,
            'athlete_id' => 123,
        ]);

        $mockedStravaActivitiesService = Mockery::mock(StravaActivitiesService::class);
        $mockedStravaActivitiesService->shouldReceive('deleteStoredActivityByStravaId')
            ->once()
            ->with(
                Mockery::on(fn ($stravaConnectionArg) => $stravaConnectionArg->is($stravaConnection)),
                456
            );
        app()->instance(StravaActivitiesService::class, $mockedStravaActivitiesService);

        app(HandleStravaActivityWebhookJob::class, [
            'aspectType' => StravaWebhookAspectTypeEnum::DELETE,
            'stravaAthleteId' => $stravaConnection->athlete_id,
            'stravaActivityId' => 456,
        ])->handle();

        Event::assertNotDispatched(StravaActivityWebhookProcessedEvent::class);
    }
}
