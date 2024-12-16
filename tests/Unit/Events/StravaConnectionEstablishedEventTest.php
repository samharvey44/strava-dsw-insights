<?php

namespace Tests\Unit\Events;

use App\Events\StravaConnectionEstablishedEvent;
use App\Listeners\HandleStravaConnectionEstablishedListener;
use App\Models\StravaConnection;
use App\Models\User;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Queue;
use Tests\TestCase;

class StravaConnectionEstablishedEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_listener_is_dispatched_when_event_is_fired(): void
    {
        Queue::fake([
            CallQueuedListener::class,
        ]);

        $stravaConnection = StravaConnection::factory()->create([
            'user_id' => User::factory()->create()->id,
        ]);
        StravaConnectionEstablishedEvent::dispatch($stravaConnection);

        Queue::assertPushed(CallQueuedListener::class, function (CallQueuedListener $job) {
            return $job->class === HandleStravaConnectionEstablishedListener::class;
        });
    }
}
