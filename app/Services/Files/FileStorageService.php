<?php

namespace App\Services\Files;

use Illuminate\Http\UploadedFile;
use Storage;
use Str;

class FileStorageService
{
    public function storePubliclyWithRandomFilename(string $path, UploadedFile $file): string
    {
        $fileExtension = $file->getClientOriginalExtension();

        $filename = $this->generateRandomFilename($path, $fileExtension, 'public');

        $storedPath = Storage::disk('public')->putFileAs($path, $file, $filename);

        return "storage/{$storedPath}";
    }

    public function deletePubliclyStoredFile(string $path): void
    {
        $pathWithStorageRemoved = explode('storage/', $path)[1] ?? $path;

        Storage::disk('public')->delete($pathWithStorageRemoved);
    }

    private function generateRandomFilename(string $path, string $extension, string $disk): string
    {
        $filename = Str::random(50);

        if (Storage::disk($disk)->exists("{$path}/{$filename}.{$extension}")) {
            return $this->generateRandomFilename($path, $extension, $disk);
        }

        return "{$filename}.{$extension}";
    }
}
