<?php

namespace Tests\Unit\Services\Files;

use App\Services\Files\FileStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class FileStorageServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_publicly_with_random_filename(): void
    {
        $path = fake()->word().'/'.fake()->word().'/'.fake()->word();
        $file = UploadedFile::fake()->image('image.jpg');

        Str::createRandomStringsUsing(function () {
            return 'random';
        });

        try {
            Storage::shouldReceive('disk')
                ->with('public')
                ->andReturnSelf();
            Storage::shouldReceive('exists')
                ->with("$path/random.jpg")
                ->andReturnFalse();
            Storage::shouldReceive('disk')
                ->with('public')
                ->andReturnSelf();
            Storage::shouldReceive('putFileAs')
                ->with($path, $file, 'random.jpg')
                ->andReturn("$path/random.jpg");

            $storedPath = app(FileStorageService::class)->storePubliclyWithRandomFilename($path, $file);

            $this->assertEquals("storage/{$path}/random.jpg", $storedPath);
        } finally {
            Str::createRandomStringsNormally();
        }
    }

    public function test_delete_publicly_stored_file_with_path_containing_storage_prefix(): void
    {
        $pathWithoutStorage = sprintf('%s/%s/%s', fake()->word(), fake()->word(), fake()->word());
        $pathWithStorage = sprintf('storage/%s', $pathWithoutStorage);

        Storage::shouldReceive('disk')
            ->with('public')
            ->andReturnSelf();
        Storage::shouldReceive('delete')
            ->with($pathWithoutStorage)
            ->andReturnTrue();

        app(FileStorageService::class)->deletePubliclyStoredFile($pathWithStorage);

        $this->assertTrue(true);
    }

    public function test_delete_publicly_stored_file_with_path_not_containing_storage_prefix(): void
    {
        $pathWithoutStorage = sprintf('%s/%s/%s', fake()->word(), fake()->word(), fake()->word());

        Storage::shouldReceive('disk')
            ->with('public')
            ->andReturnSelf();
        Storage::shouldReceive('delete')
            ->with($pathWithoutStorage)
            ->andReturnTrue();

        app(FileStorageService::class)->deletePubliclyStoredFile($pathWithoutStorage);

        $this->assertTrue(true);
    }
}
