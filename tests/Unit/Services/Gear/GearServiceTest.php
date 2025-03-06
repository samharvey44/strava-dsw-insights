<?php

namespace Tests\Unit\Services\Gear;

use App\Models\Gear;
use App\Models\User;
use App\Services\Files\FileStorageService;
use App\Services\Gear\GearService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Mockery;
use Tests\TestCase;

class GearServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_gear_with_image(): void
    {
        $this->actingAs($user = User::factory()->create());

        $name = fake()->word();
        $description = fake()->sentence();
        $firstUsed = Carbon::parse(fake()->date());
        $decommissioned = Carbon::parse(fake()->date());
        $image = UploadedFile::fake()->image('image.jpg');

        $mockedFileStorageService = Mockery::mock(FileStorageService::class);
        $mockedFileStorageService->shouldReceive('storePubliclyWithRandomFilename')
            ->with('gear_images', Mockery::type(UploadedFile::class))
            ->once()
            ->andReturn('some_path.jpg');
        app()->instance(FileStorageService::class, $mockedFileStorageService);

        app(GearService::class)->store(
            $user->id,
            $name,
            $description,
            $firstUsed,
            $decommissioned,
            $image
        );

        $this->assertDatabaseHas('gears', [
            'user_id' => $user->id,
            'name' => $name,
            'description' => $description,
            'first_used' => $firstUsed->toDateString(),
            'decommissioned' => $decommissioned->toDateString(),
            'image_path' => 'some_path.jpg',
        ]);
    }

    public function test_store_gear_without_image(): void
    {
        $this->actingAs($user = User::factory()->create());

        $name = fake()->word();
        $description = fake()->sentence();
        $firstUsed = Carbon::parse(fake()->date());
        $decommissioned = Carbon::parse(fake()->date());

        $mockedFileStorageService = Mockery::mock(FileStorageService::class);
        $mockedFileStorageService->shouldNotReceive('storePubliclyWithRandomFilename');
        app()->instance(FileStorageService::class, $mockedFileStorageService);

        app(GearService::class)->store(
            $user->id,
            $name,
            $description,
            $firstUsed,
            $decommissioned,
            null
        );

        $this->assertDatabaseHas('gears', [
            'user_id' => $user->id,
            'name' => $name,
            'description' => $description,
            'first_used' => $firstUsed->toDateString(),
            'decommissioned' => $decommissioned->toDateString(),
            'image_path' => null,
        ]);
    }

    public function test_update_gear_has_previous_image_new_image_uploaded(): void
    {
        $this->actingAs($user = User::factory()->create());

        $gear = Gear::factory()->create([
            'name' => 'Old Name',
            'description' => 'Old Description',
            'first_used' => Carbon::parse('2021-01-01'),
            'decommissioned' => Carbon::parse('2021-01-02'),
            'image_path' => 'old_image.jpg',
            'user_id' => $user->id,
        ]);

        $name = fake()->word();
        $description = fake()->sentence();
        $firstUsed = Carbon::parse(fake()->date());
        $decommissioned = Carbon::parse(fake()->date());
        $image = UploadedFile::fake()->image('new_image.jpg');

        $mockedFileStorageService = Mockery::mock(FileStorageService::class);
        $mockedFileStorageService->shouldReceive('deletePubliclyStoredFile')
            ->with('old_image.jpg')
            ->once();
        $mockedFileStorageService->shouldReceive('storePubliclyWithRandomFilename')
            ->with('gear_images', Mockery::type(UploadedFile::class))
            ->once()
            ->andReturn('new_image.jpg');
        app()->instance(FileStorageService::class, $mockedFileStorageService);

        app(GearService::class)->update(
            $gear,
            $name,
            $description,
            $firstUsed,
            $decommissioned,
            $image
        );

        $this->assertDatabaseHas('gears', [
            'id' => $gear->id,
            'name' => $name,
            'description' => $description,
            'first_used' => $firstUsed->toDateString(),
            'decommissioned' => $decommissioned->toDateString(),
            'image_path' => 'new_image.jpg',
        ]);
    }

    public function test_update_gear_doesnt_have_previous_image_new_image_uploaded(): void
    {
        $this->actingAs($user = User::factory()->create());

        $gear = Gear::factory()->create([
            'name' => 'Old Name',
            'description' => 'Old Description',
            'first_used' => Carbon::parse('2021-01-01'),
            'decommissioned' => Carbon::parse('2021-01-02'),
            'image_path' => null,
            'user_id' => $user->id,
        ]);

        $name = fake()->word();
        $description = fake()->sentence();
        $firstUsed = Carbon::parse(fake()->date());
        $decommissioned = Carbon::parse(fake()->date());
        $image = UploadedFile::fake()->image('new_image.jpg');

        $mockedFileStorageService = Mockery::mock(FileStorageService::class);
        $mockedFileStorageService->shouldNotReceive('deletePubliclyStoredFile');
        $mockedFileStorageService->shouldReceive('storePubliclyWithRandomFilename')
            ->with('gear_images', Mockery::type(UploadedFile::class))
            ->once()
            ->andReturn('new_image.jpg');
        app()->instance(FileStorageService::class, $mockedFileStorageService);

        app(GearService::class)->update(
            $gear,
            $name,
            $description,
            $firstUsed,
            $decommissioned,
            $image
        );

        $this->assertDatabaseHas('gears', [
            'id' => $gear->id,
            'name' => $name,
            'description' => $description,
            'first_used' => $firstUsed->toDateString(),
            'decommissioned' => $decommissioned->toDateString(),
            'image_path' => 'new_image.jpg',
        ]);
    }

    public function test_update_gear_has_previous_image_new_image_not_uploaded(): void
    {
        $this->actingAs($user = User::factory()->create());

        $gear = Gear::factory()->create([
            'name' => 'Old Name',
            'description' => 'Old Description',
            'first_used' => Carbon::parse('2021-01-01'),
            'decommissioned' => Carbon::parse('2021-01-02'),
            'image_path' => 'old_image.jpg',
            'user_id' => $user->id,
        ]);

        $name = fake()->word();
        $description = fake()->sentence();
        $firstUsed = Carbon::parse(fake()->date());
        $decommissioned = Carbon::parse(fake()->date());

        $mockedFileStorageService = Mockery::mock(FileStorageService::class);
        $mockedFileStorageService->shouldNotReceive('deletePubliclyStoredFile');
        $mockedFileStorageService->shouldNotReceive('storePubliclyWithRandomFilename');
        app()->instance(FileStorageService::class, $mockedFileStorageService);

        app(GearService::class)->update(
            $gear,
            $name,
            $description,
            $firstUsed,
            $decommissioned,
            null
        );

        $this->assertDatabaseHas('gears', [
            'id' => $gear->id,
            'name' => $name,
            'description' => $description,
            'first_used' => $firstUsed->toDateString(),
            'decommissioned' => $decommissioned->toDateString(),
            'image_path' => 'old_image.jpg',
        ]);
    }

    public function test_update_gear_doesnt_have_previous_image_new_image_not_uploaded(): void
    {
        $this->actingAs($user = User::factory()->create());

        $gear = Gear::factory()->create([
            'name' => 'Old Name',
            'description' => 'Old Description',
            'first_used' => Carbon::parse('2021-01-01'),
            'decommissioned' => Carbon::parse('2021-01-02'),
            'image_path' => null,
            'user_id' => $user->id,
        ]);

        $name = fake()->word();
        $description = fake()->sentence();
        $firstUsed = Carbon::parse(fake()->date());
        $decommissioned = Carbon::parse(fake()->date());

        $mockedFileStorageService = Mockery::mock(FileStorageService::class);
        $mockedFileStorageService->shouldNotReceive('deletePubliclyStoredFile');
        $mockedFileStorageService->shouldNotReceive('storePubliclyWithRandomFilename');
        app()->instance(FileStorageService::class, $mockedFileStorageService);

        app(GearService::class)->update(
            $gear,
            $name,
            $description,
            $firstUsed,
            $decommissioned,
            null
        );

        $this->assertDatabaseHas('gears', [
            'id' => $gear->id,
            'name' => $name,
            'description' => $description,
            'first_used' => $firstUsed->toDateString(),
            'decommissioned' => $decommissioned->toDateString(),
            'image_path' => null,
        ]);
    }

    public function test_destroy_gear_has_image(): void
    {
        $this->actingAs($user = User::factory()->create());

        $gear = Gear::factory()->create([
            'image_path' => 'some_image.jpg',
            'user_id' => $user->id,
        ]);

        $mockedFileStorageService = Mockery::mock(FileStorageService::class);
        $mockedFileStorageService->shouldReceive('deletePubliclyStoredFile')
            ->with('some_image.jpg')
            ->once();
        app()->instance(FileStorageService::class, $mockedFileStorageService);

        app(GearService::class)->destroy($gear);

        $this->assertDatabaseMissing('gears', [
            'id' => $gear->id,
        ]);
    }

    public function test_destroy_gear_doesnt_have_image(): void
    {
        $this->actingAs($user = User::factory()->create());

        $gear = Gear::factory()->create([
            'image_path' => null,
            'user_id' => $user->id,
        ]);

        $mockedFileStorageService = Mockery::mock(FileStorageService::class);
        $mockedFileStorageService->shouldNotReceive('deletePubliclyStoredFile');
        app()->instance(FileStorageService::class, $mockedFileStorageService);

        app(GearService::class)->destroy($gear);

        $this->assertDatabaseMissing('gears', [
            'id' => $gear->id,
        ]);
    }
}
