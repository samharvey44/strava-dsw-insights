<?php

namespace App\Services\Gear;

use App\Models\Gear;
use App\Services\Files\FileStorageService;
use DateTime;
use Illuminate\Http\UploadedFile;

class GearService
{
    public function store(
        string $userId,
        string $name,
        ?string $description,
        ?DateTime $firstUsed,
        ?DateTime $decommissioned,
        ?UploadedFile $image,
    ): void {
        $imagePath = is_null($image)
            ? null
            : app(FileStorageService::class)->storePubliclyWithRandomFilename(
                'gear_images',
                $image
            );

        Gear::create([
            'user_id' => $userId,
            'name' => $name,
            'description' => $description,
            'first_used' => $firstUsed,
            'decommissioned' => $decommissioned,
            'image_path' => $imagePath,
        ]);
    }

    public function update(
        Gear $gear,
        string $name,
        ?string $description,
        ?DateTime $firstUsed,
        ?DateTime $decommissioned,
        ?UploadedFile $image
    ): void {
        if (! is_null($image) && ! is_null($gear->image_path)) {
            app(FileStorageService::class)->deletePubliclyStoredFile($gear->image_path);
        }

        $imagePath = is_null($image)
            ? $gear->image_path
            : app(FileStorageService::class)->storePubliclyWithRandomFilename(
                'gear_images',
                $image
            );

        $gear->update([
            'name' => $name,
            'description' => $description,
            'first_used' => $firstUsed,
            'decommissioned' => $decommissioned,
            'image_path' => $imagePath,
        ]);
    }

    public function destroy(Gear $gear): void
    {
        $gear->delete();

        if (is_null($gear->image_path)) {
            return;
        }

        app(FileStorageService::class)->deletePubliclyStoredFile($gear->image_path);
    }
}
