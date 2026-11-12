<?php

namespace App\Modules\Report\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ReportPhotoStorage
{
    /** @return array{path: string, mime: string, size: int} */
    public function store(UploadedFile $photo, int $reportId, string $disk): array
    {
        $path = Storage::disk($disk)->putFileAs('reports/'.$reportId, $photo, Str::uuid().'.'.$photo->extension());
        if ($path === false) {
            throw new RuntimeException('Report photo could not be stored.');
        }

        return ['path' => $path, 'mime' => $photo->getMimeType(), 'size' => $photo->getSize()];
    }
}
