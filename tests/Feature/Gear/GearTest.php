<?php

namespace Tests\Feature\Gear;

use App\Models\Gear;
use App\Models\User;
use App\Services\Gear\GearService;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Testing\File;
use Illuminate\Http\UploadedFile;
use Mockery;
use Tests\TestCase;

class GearTest extends TestCase
{
    use RefreshDatabase;

    public function test_view_gear_page(): void
    {
        $this->actingAs($user = User::factory()->create());

        Gear::factory(3)->create([
            'user_id' => $user->id,
        ]);
        Gear::factory()->create([
            'user_id' => User::factory()->create()->id,
        ]);

        $response = $this->get(route('gear'));
        $response->assertViewIs('pages.gear.index');
        $response->assertViewHas(
            'gear',
            Gear::query()
                ->where('user_id', $user->id)
                ->with([
                    'reminders' => fn ($query) => $query->orderByDesc('created_at'),
                ])
                ->orderByDesc('created_at')
                ->paginate(20)
        );
    }

    public function test_view_create_gear_page(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('gear.create'));
        $response->assertViewIs('pages.gear.create');
    }

    public function test_can_store_gear(): void
    {
        $this->actingAs($user = User::factory()->create());

        $this->freezeSecond();

        $mockedGearService = Mockery::mock(GearService::class);
        $mockedGearService->shouldReceive('store')
            ->once()
            ->with(
                $user->id,
                'Test Gear',
                'Test Description',
                Mockery::on(fn (CarbonInterface $firstUsedArg) => $firstUsedArg->timestamp === now()->subYear()->timestamp),
                Mockery::on(fn (CarbonInterface $decommissionedArg) => $decommissionedArg->timestamp === now()->timestamp),
                Mockery::type(File::class)
            );
        app()->instance(GearService::class, $mockedGearService);

        $response = $this->post(route('gear.store'), [
            'name' => 'Test Gear',
            'description' => 'Test Description',
            'first_used' => now()->subYear(),
            'decommissioned' => now(),
            'image' => UploadedFile::fake()->image('test.jpg'),
        ]);

        $response->assertRedirect(route('gear'));
        $response->assertSessionHas('success', 'Gear created successfully!');
    }

    public function test_cannot_store_gear_without_name(): void
    {
        $this->actingAs(User::factory()->create());

        $mockedGearService = Mockery::mock(GearService::class);
        $mockedGearService->shouldNotReceive('store');
        app()->instance(GearService::class, $mockedGearService);

        $response = $this->post(route('gear.store'), [
            'description' => 'Test Description',
            'first_used' => now()->subYear(),
            'decommissioned' => now(),
            'image' => UploadedFile::fake()->image('test.jpg'),
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_can_view_edit_gear_page(): void
    {
        $this->actingAs($user = User::factory()->create());

        $gear = Gear::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->get(route('gear.edit', $gear));
        $response->assertViewIs('pages.gear.edit');
        $response->assertViewHas('gear', $gear);
    }

    public function test_can_update_gear(): void
    {
        $this->actingAs($user = User::factory()->create());

        $this->freezeSecond();

        $gear = Gear::factory()->create([
            'user_id' => $user->id,
        ]);

        $mockedGearService = Mockery::mock(GearService::class);
        $mockedGearService->shouldReceive('update')
            ->once()
            ->with(
                Mockery::on(fn ($gearArg) => $gearArg->is($gear)),
                'Test Gear',
                'Test Description',
                Mockery::on(fn (CarbonInterface $firstUsedArg) => $firstUsedArg->timestamp === now()->subYear()->timestamp),
                Mockery::on(fn (CarbonInterface $decommissionedArg) => $decommissionedArg->timestamp === now()->timestamp),
                Mockery::type(File::class)
            );
        app()->instance(GearService::class, $mockedGearService);

        $response = $this->patch(route('gear.update', $gear), [
            'name' => 'Test Gear',
            'description' => 'Test Description',
            'first_used' => now()->subYear(),
            'decommissioned' => now(),
            'image' => UploadedFile::fake()->image('test.jpg'),
        ]);

        $response->assertRedirect(route('gear'));
        $response->assertSessionHas('success', 'Gear updated successfully!');
    }

    public function test_cannot_update_gear_without_name(): void
    {
        $this->actingAs($user = User::factory()->create());

        $gear = Gear::factory()->create([
            'user_id' => $user->id,
        ]);

        $mockedGearService = Mockery::mock(GearService::class);
        $mockedGearService->shouldNotReceive('update');
        app()->instance(GearService::class, $mockedGearService);

        $response = $this->patch(route('gear.update', $gear), [
            'description' => 'Test Description',
            'first_used' => now()->subYear(),
            'decommissioned' => now(),
            'image' => UploadedFile::fake()->image('test.jpg'),
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_can_destroy_gear_without_redirect_path(): void
    {
        $this->actingAs($user = User::factory()->create());

        $gear = Gear::factory()->create([
            'user_id' => $user->id,
        ]);

        $mockedGearService = Mockery::mock(GearService::class);
        $mockedGearService->shouldReceive('destroy')
            ->once()
            ->with(Mockery::on(fn ($gearArg) => $gearArg->is($gear)));
        app()->instance(GearService::class, $mockedGearService);

        $response = $this->delete(route('gear.destroy', $gear));

        $response->assertRedirect(route('gear', ['page' => 1]));
        $response->assertSessionHas('success', 'Gear deleted successfully!');
    }

    public function test_can_destroy_gear_with_redirect_path(): void
    {
        $this->actingAs($user = User::factory()->create());

        $gear = Gear::factory()->create([
            'user_id' => $user->id,
        ]);

        $mockedGearService = Mockery::mock(GearService::class);
        $mockedGearService->shouldReceive('destroy')
            ->once()
            ->with(Mockery::on(fn ($gearArg) => $gearArg->is($gear)));
        app()->instance(GearService::class, $mockedGearService);

        $response = $this->delete(route('gear.destroy', [
            'gear' => $gear,
            'redirect_page' => 2,
        ]));

        $response->assertRedirect(route('gear', ['page' => 2]));
        $response->assertSessionHas('success', 'Gear deleted successfully!');
    }
}
