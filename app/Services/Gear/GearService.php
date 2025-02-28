<?php

namespace App\Services\Gear;

use App\Models\Gear;
use App\Services\Files\FileStorageService;
use DateTime;
use Illuminate\Http\UploadedFile;

class GearService
{
    public function store(
        string $name,
        ?string $description,
        ?DateTime $firstUsed,
        ?DateTime $decommissioned,
        ?UploadedFile $image
    ): void {
        $imagePath = is_null($image)
            ? null
            : app(FileStorageService::class)->storePubliclyWithRandomFilename(
                'gear_images',
                $image
            );

        Gear::create([
            'user_id' => auth()->id(),
            'name' => $name,
            'description' => $description,
            'first_used' => $firstUsed,
            'decommissioned' => $decommissioned,
            'image_path' => $imagePath,
        ]);
    }
}
