<?php

namespace Slimani\MediaManager\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File as FileFacade;
use Illuminate\Support\Facades\Schema;
use Slimani\MediaManager\Models\File as MediaFile;
use Slimani\MediaManager\Models\Folder;
use Slimani\MediaManager\Models\Tag;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaSeeder extends Seeder
{
    public function run(): void
    {
        // 0. Clean up existing data
        Schema::disableForeignKeyConstraints();
        Media::where('model_type', MediaFile::class)->delete();
        MediaFile::truncate();
        Folder::truncate();
        Tag::truncate();
        DB::table('media_taggables')->truncate();
        Schema::enableForeignKeyConstraints();

        $tags = collect(['Urgent', 'Marketing', 'Assets', 'Confidential', 'Draft', 'Public'])->map(function ($name) {
            return Tag::create(['name' => $name]);
        });

        $userModel = config('auth.providers.users.model');
        $user = $userModel::first() ?? $userModel::factory()->create(['email' => 'admin@example.com']);

        // 1. Root Folders
        $folders = [
            'Documents' => ['Reports', 'Invoices', 'Legal'],
            'Marketing' => ['Campaigns', 'Social Media'],
            'Assets' => ['Logos', 'Icons'],
            'Personal' => [],
            'Vault' => [],
            'Archives' => [],
        ];

        $folderModels = [];

        foreach ($folders as $rootName => $subFolders) {
            $root = Folder::create(['name' => $rootName]);
            $folderModels[$rootName] = $root;

            if (rand(0, 1)) {
                $root->tags()->attach($tags->random(rand(1, min(2, $tags->count())))->pluck('id'));
            }

            foreach ($subFolders as $subName) {
                $sub = Folder::create([
                    'name' => $subName,
                    'parent_id' => $root->id,
                ]);
                $folderModels["$rootName/$subName"] = $sub;

                if (rand(0, 1)) {
                    $sub->tags()->attach($tags->random(rand(1, min(2, $tags->count())))->pluck('id'));
                }
            }
        }

        // 2. Map Assets
        $assetPath = storage_path('app/seed-assets');
        if (! FileFacade::exists($assetPath)) {
            $this->command->error("Seed assets directory not found at: $assetPath");

            return;
        }

        $allFiles = FileFacade::files($assetPath);
        $maxSize = 10 * 1024 * 1024; // 10MB
        $filePool = collect($allFiles)
            ->filter(fn ($f) => ! str_contains($f->getFilename(), '.part'))
            ->filter(fn ($f) => filesize($f->getPathname()) < $maxSize);

        $this->command->info('Found '.$filePool->count().' assets to seed.');

        $folderKeys = array_keys($folderModels);

        foreach ($filePool as $index => $file) {
            // Assign to a random folder (including root)
            $folderKey = $folderKeys[$index % count($folderKeys)];
            $folder = $folderModels[$folderKey];

            $filename = $file->getFilename();
            $name = pathinfo($filename, PATHINFO_FILENAME);

            $mediaFile = MediaFile::create([
                'uploaded_by_user_id' => $user->id,
                'folder_id' => $folder->id,
                'name' => $name,
            ]);

            // Using standard Spatie Media Library "upload/association" methods
            // As per V11 docs: https://spatie.be/docs/laravel-medialibrary/v11/basic-usage/associating-files
            $media = $mediaFile->addMedia($file->getPathname())
                ->preservingOriginal()
                ->toMediaCollection('default');

            if (rand(0, 1)) {
                $mediaFile->tags()->attach($tags->random(rand(1, 2))->pluck('id'));
            }

            $mediaFile->update([
                'name' => $media->file_name,
                'size' => $media->size,
                'mime_type' => $media->mime_type,
                'extension' => $media->extension,
                'width' => $media->getCustomProperty('width'),
                'height' => $media->getCustomProperty('height'),
            ]);

            $this->command->info("Seeded: {$filename} into {$folderKey}");
        }
    }
}
