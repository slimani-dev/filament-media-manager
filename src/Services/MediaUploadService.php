<?php

namespace Slimani\MediaManager\Services;

use Illuminate\Http\UploadedFile;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Slimani\MediaManager\Models\File;
use Slimani\MediaManager\Models\Folder;

class MediaUploadService
{
    public function upload(
        UploadedFile|TemporaryUploadedFile $file,
        ?Folder $folder = null,
        ?int $userId = null,
        array $metadata = []
    ): File {
        $fileModel = File::create([
            'folder_id' => $folder?->id,
            'uploaded_by_user_id' => $userId,
            'name' => $metadata['name'] ?? $file->getClientOriginalName(),
            'caption' => $metadata['caption'] ?? $file->getClientOriginalName(),
            'alt_text' => $metadata['alt_text'] ?? $file->getClientOriginalName(),
        ]);

        $fileModel->addMedia($file)
            ->toMediaCollection('default');

        return $fileModel;
    }
}
