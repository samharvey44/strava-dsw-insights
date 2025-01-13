<?php

namespace Tests\Unit\Events;

use App\Events\StravaActivityReadyForDswAnalysisEvent;
use App\Listeners\PerformStravaActivityDswAnalysisListener;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Queue;
use Tests\TestCase;

class StravaActivityReadyForDswAnalysisEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_listener_is_dispatched_when_event_is_fired(): void
    {
        Queue::fake([
            CallQueuedListener::class,
        ]);

        StravaActivityReadyForDswAnalysisEvent::dispatch(123, 456);

        Queue::assertPushed(CallQueuedListener::class, function (CallQueuedListener $job) {
            return $job->class === PerformStravaActivityDswAnalysisListener::class;
        });
    }
}
