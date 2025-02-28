<?php

namespace App\Services\Files;

use Illuminate\Http\UploadedFile;
use Storage;
use Str;

class FileStorageService
{
    public function storePubliclyWithRandomFilename(string $path, UploadedFile $file): string
    {
        $filename = $this->generateRandomFilename($path);

        $storedPath = Storage::disk('public')->putFileAs($path, $file, "{$filename}.{$file->extension()}");

        return "storage/{$storedPath}";
    }

    private function generateRandomFilename(string $path): string
    {
        $filename = Str::random(50);

        if (Storage::disk('public')->exists("{$path}/{$filename}")) {
            return $this->generateRandomFilename($path);
        }

        return $filename;
    }
}
