<?php

namespace Tests\Feature\Gear\Reminders;

use App\Models\Gear;
use App\Models\GearReminder;
use App\Models\User;
use App\Services\Gear\Reminders\GearRemindersService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class GearRemindersTest extends TestCase
{
    use RefreshDatabase;

    public function test_gear_reminders_modal_contents(): void
    {
        $this->actingAs($user = User::factory()->create());

        $gear = Gear::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->get(route('gear.reminders.modal-contents', $gear));

        $response->assertViewIs('pages.gear.partials.gear_reminder_modal_contents');
        $response->assertViewHas('gearItem', $gear);
    }

    public function test_store_gear_reminder(): void
    {
        $this->actingAs($user = User::factory()->create());

        $gear = Gear::factory()->create([
            'user_id' => $user->id,
        ]);

        $mockedGearReminderService = Mockery::mock(GearRemindersService::class);
        $mockedGearReminderService->shouldReceive('store')
            ->once()
            ->with(
                $gear->id,
                'Test reminder',
                4,
                3,
            );
        app()->instance(GearRemindersService::class, $mockedGearReminderService);

        $response = $this->post(route('gear.reminders.store', $gear), [
            'name' => 'Test reminder',
            'trigger_after_number_of_activities' => 4,
            'current_number_of_activities' => 3,
        ]);

        $response->assertSessionHas('success', 'Reminder created successfully!');
    }

    public function test_update_gear_reminder(): void
    {
        $this->actingAs($user = User::factory()->create());

        $gear = Gear::factory()->create([
            'user_id' => $user->id,
        ]);

        $reminder = GearReminder::factory()->create([
            'gear_id' => $gear->id,
        ]);

        $mockedGearReminderService = Mockery::mock(GearRemindersService::class);
        $mockedGearReminderService->shouldReceive('update')
            ->once()
            ->with(
                Mockery::on(fn ($reminderArg) => $reminderArg->is($reminder)),
                'Updated reminder',
                5,
                4,
            );
        app()->instance(GearRemindersService::class, $mockedGearReminderService);

        $response = $this->patch(route('gear.reminders.update', [$gear, $reminder]), [
            'name' => 'Updated reminder',
            'trigger_after_number_of_activities' => 5,
            'current_number_of_activities' => 4,
        ]);

        $response->assertSessionHas('success', 'Reminder updated successfully!');
    }

    public function test_destroy_gear_reminder(): void
    {
        $this->actingAs($user = User::factory()->create());

        $gear = Gear::factory()->create([
            'user_id' => $user->id,
        ]);

        $reminder = GearReminder::factory()->create([
            'gear_id' => $gear->id,
        ]);

        $mockedGearReminderService = Mockery::mock(GearRemindersService::class);
        $mockedGearReminderService->shouldReceive('destroy')
            ->once()
            ->with(Mockery::on(fn ($reminderArg) => $reminderArg->is($reminder)));
        app()->instance(GearRemindersService::class, $mockedGearReminderService);

        $response = $this->delete(route('gear.reminders.destroy', [$gear, $reminder]));

        $response->assertSessionHas('success', 'Reminder deleted successfully!');
    }
}
