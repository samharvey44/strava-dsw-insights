<?php

namespace Tests\Unit\Policies;

use App\Models\Gear;
use App\Models\User;
use App\Policies\GearPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GearPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_their_own_gear(): void
    {
        $user = User::factory()->create();
        $gear = Gear::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->assertTrue(app(GearPolicy::class)->update($user, $gear));
    }

    public function test_user_cannot_update_other_users_gear(): void
    {
        $user = User::factory()->create();
        $gear = Gear::factory()->create([
            'user_id' => User::factory()->create()->id,
        ]);

        $this->assertFalse(app(GearPolicy::class)->update($user, $gear));
    }

    public function test_user_can_destroy_their_own_gear(): void
    {
        $user = User::factory()->create();
        $gear = Gear::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->assertTrue(app(GearPolicy::class)->destroy($user, $gear));
    }

    public function test_user_cannot_destroy_other_users_gear(): void
    {
        $user = User::factory()->create();
        $gear = Gear::factory()->create([
            'user_id' => User::factory()->create()->id,
        ]);

        $this->assertFalse(app(GearPolicy::class)->destroy($user, $gear));
    }

    public function test_user_can_handle_reminders_for_their_own_gear(): void
    {
        $user = User::factory()->create();
        $gear = Gear::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->assertTrue(app(GearPolicy::class)->reminders($user, $gear));
    }

    public function test_user_cannot_handle_reminders_for_other_users_gear(): void
    {
        $user = User::factory()->create();
        $gear = Gear::factory()->create([
            'user_id' => User::factory()->create()->id,
        ]);

        $this->assertFalse(app(GearPolicy::class)->reminders($user, $gear));
    }
}
