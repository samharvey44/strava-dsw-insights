<?php

namespace App\Services\Files;

use Illuminate\Http\UploadedFile;
use Storage;
use Str;

class FileStorageService
{
    public function storePubliclyWithRandomFilename(string $path, UploadedFile $file): string
    {
        $filename = $this->generateRandomFilename($path, 'public');

        $storedPath = Storage::disk('public')->putFileAs($path, $file, "{$filename}.{$file->extension()}");

        return "storage/{$storedPath}";
    }

    public static function deletePubliclyStoredFile(string $path): void
    {
        $pathWithStorageRemoved = ltrim($path, 'storage/');

        Storage::disk('public')->delete($pathWithStorageRemoved);
    }

    private function generateRandomFilename(string $path, string $disk): string
    {
        $filename = Str::random(50);

        if (Storage::disk($disk)->exists("{$path}/{$filename}")) {
            return $this->generateRandomFilename($path, $disk);
        }

        return $filename;
    }
}
