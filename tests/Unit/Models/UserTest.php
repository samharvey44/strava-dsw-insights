<?php

namespace Tests\Unit\Models;

use App\Models\StravaConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_has_active_strava_connection_with_active_connection(): void
    {
        $user = User::factory()->create();
        StravaConnection::factory()->create([
            'user_id' => $user->id,
            'active' => true,
        ]);

        $this->assertTrue($user->hasActiveStravaConnection());
    }

    public function test_has_active_strava_connection_with_no_existing_connection(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($user->hasActiveStravaConnection());
    }

    public function test_has_active_strava_connection_with_inactive_connection(): void
    {
        $user = User::factory()->create();
        StravaConnection::factory()->create([
            'user_id' => $user->id,
            'active' => false,
        ]);

        $this->assertFalse($user->hasActiveStravaConnection());
    }
}
