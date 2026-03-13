<?php

namespace Slimani\MediaManager\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File as FileFacade;
use Illuminate\Support\Facades\Storage;
use Slimani\MediaManager\Models\File as MediaFile;
use Slimani\MediaManager\Models\Folder;

class FolderEightSeeder extends Seeder
{
    public function run(): void
    {
        $folderId = 8;
        $targetFolder = Folder::find($folderId);

        if (! $targetFolder) {
            $this->command->error("Folder with ID {$folderId} not found. Creating it...");
            $targetFolder = Folder::create([
                'id' => $folderId,
                'name' => 'Test Folder 8',
            ]);
        }

        $userModel = config('auth.providers.users.model') ?? 'App\Models\User';
        $user = $userModel::first() ?: $userModel::factory()->create();

        $assetPath = storage_path('app/seed-assets');
        $existingAssets = FileFacade::exists($assetPath) ? FileFacade::files($assetPath) : [];
        
        $maxSize = 10 * 1024 * 1024; // 10MB limit
        $filteredAssets = collect($existingAssets)->filter(fn($f) => $f->getSize() < $maxSize);
        
        $images = $filteredAssets->filter(fn($f) => in_array($f->getExtension(), ['jpg', 'jpeg', 'png', 'svg']));
        $videos = $filteredAssets->filter(fn($f) => in_array($f->getExtension(), ['mp4', 'webm', 'mov']));
        $pdfs = $filteredAssets->filter(fn($f) => $f->getExtension() === 'pdf');

        $this->command->info("Starting to seed 60 files into folder {$folderId}...");

        for ($i = 1; $i <= 60; $i++) {
            $type = match ($i % 6) {
                0 => 'image',
                1 => 'video',
                2 => 'pdf',
                3 => 'excel',
                4 => 'word',
                5 => 'txt',
            };

            $filename = "";
            $tempPath = null;

            if ($type === 'image' && $images->isNotEmpty()) {
                $source = $images->random();
                $filename = "image_{$i}." . $source->getExtension();
                $tempPath = $source->getPathname();
            } elseif ($type === 'video' && $videos->isNotEmpty()) {
                $source = $videos->random();
                $filename = "video_{$i}." . $source->getExtension();
                $tempPath = $source->getPathname();
            } elseif ($type === 'pdf' && $pdfs->isNotEmpty()) {
                $source = $pdfs->random();
                $filename = "document_{$i}.pdf";
                $tempPath = $source->getPathname();
            } elseif ($type === 'txt') {
                $filename = "note_{$i}.txt";
                $tempPath = storage_path("app/temp_{$filename}");
                FileFacade::put($tempPath, "This is a dummy text file number {$i}");
            } elseif ($type === 'excel') {
                $filename = "sheet_{$i}.xlsx";
                $tempPath = storage_path("app/temp_{$filename}");
                // Create a dummy CSV that looks like Excel for seeding purposes
                FileFacade::put($tempPath, "ID,Name,Value\n{$i},Test,{$i}00");
            } elseif ($type === 'word') {
                $filename = "doc_{$i}.docx";
                $tempPath = storage_path("app/temp_{$filename}");
                FileFacade::put($tempPath, "This is a dummy word document content for file {$i}");
            } else {
                // Fallback to text if asset missing
                $filename = "fallback_{$i}.txt";
                $tempPath = storage_path("app/temp_{$filename}");
                FileFacade::put($tempPath, "Fallback content for file {$i}");
            }

            $name = pathinfo($filename, PATHINFO_FILENAME);

            $mediaFile = MediaFile::create([
                'uploaded_by_user_id' => $user->id,
                'folder_id' => $folderId,
                'name' => $name,
            ]);

            $media = $mediaFile->addMedia($tempPath)
                ->preservingOriginal()
                ->usingFileName($filename)
                ->toMediaCollection('default');

            $mediaFile->update([
                'name' => $media->file_name,
                'size' => $media->size,
                'mime_type' => $media->mime_type,
                'extension' => $media->extension,
                'width' => $media->getCustomProperty('width'),
                'height' => $media->getCustomProperty('height'),
            ]);

            // Clean up if it was a created dummy file
            if (str_contains($tempPath, 'temp_')) {
                FileFacade::delete($tempPath);
            }

            $this->command->info("Seeded {$i}/60: {$filename}");
        }

        $this->command->info("Success! 60 files seeded into folder {$folderId}.");
    }
}
