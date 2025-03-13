<?php

namespace Tests\Unit\Policies;

use App\Models\Gear;
use App\Models\GearReminder;
use App\Models\User;
use App\Policies\GearReminderPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GearReminderPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_their_own_gear_reminder(): void
    {
        $user = User::factory()->create();
        $gearReminder = GearReminder::factory()->create([
            'gear_id' => Gear::factory()->create([
                'user_id' => $user->id,
            ])->id,
        ]);

        $this->assertTrue(app(GearReminderPolicy::class)->update($user, $gearReminder));
    }

    public function test_user_cannot_update_other_users_gear_reminder(): void
    {
        $user = User::factory()->create();
        $gearReminder = GearReminder::factory()->create([
            'gear_id' => Gear::factory()->create([
                'user_id' => User::factory()->create()->id,
            ])->id,
        ]);

        $this->assertFalse(app(GearReminderPolicy::class)->update($user, $gearReminder));
    }

    public function test_user_can_destroy_their_own_gear_reminder(): void
    {
        $user = User::factory()->create();
        $gearReminder = GearReminder::factory()->create([
            'gear_id' => Gear::factory()->create([
                'user_id' => $user->id,
            ])->id,
        ]);

        $this->assertTrue(app(GearReminderPolicy::class)->destroy($user, $gearReminder));
    }

    public function test_user_cannot_destroy_other_users_gear_reminder(): void
    {
        $user = User::factory()->create();
        $gearReminder = GearReminder::factory()->create([
            'gear_id' => Gear::factory()->create([
                'user_id' => User::factory()->create()->id,
            ])->id,
        ]);

        $this->assertFalse(app(GearReminderPolicy::class)->destroy($user, $gearReminder));
    }
}
