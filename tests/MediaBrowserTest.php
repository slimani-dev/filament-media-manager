<?php

namespace Slimani\MediaManager\Tests;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Slimani\MediaManager\Livewire\MediaBrowser;
use Slimani\MediaManager\Models\File;
use Slimani\MediaManager\Models\Folder;
use Slimani\MediaManager\Tests\Models\User;

uses(TestCase::class);

it('can render media browser page', function () {
    Livewire::test(MediaBrowser::class)
        ->assertStatus(200);
});

it('can create folder', function () {
    Livewire::test(MediaBrowser::class)
        ->callAction('createFolder', ['name' => 'Test Folder'])
        ->assertDispatched('media-uploaded');

    $this->assertDatabaseHas('media_folders', ['name' => 'Test Folder']);
});

it('can navigate folders', function () {
    $folder = Folder::create(['name' => 'My Folder']);

    Livewire::test(MediaBrowser::class)
        ->set('currentFolderId', $folder->id)
        ->assertSet('currentFolderId', $folder->id);
});

it('can search with folder set', function () {
    $folder = Folder::create(['name' => 'My Folder']);

    Livewire::test(MediaBrowser::class)
        ->call('setCurrentFolder', $folder->id)
        ->set('search', 'test')
        ->assertStatus(200);
});

it('scopes search to current folder tree', function () {
    $folderA = Folder::create(['name' => 'Folder A']);
    $folderB = Folder::create(['name' => 'Folder B']);

    $fileA = File::create(['name' => 'SearchMe', 'folder_id' => $folderA->id]);
    $fileB = File::create(['name' => 'SearchMe', 'folder_id' => $folderB->id]);

    Livewire::test(MediaBrowser::class)
        ->call('setCurrentFolder', $folderA->id)
        ->set('search', 'SearchMe')
        ->assertSee($fileA->name);
});

it('can upload file', function () {
    $disk = filament('media-manager')->getDisk();
    Storage::fake($disk);

    $file = UploadedFile::fake()->image('test-image.jpg');

    Livewire::test(MediaBrowser::class)
        ->callAction('upload', [
            'files' => [$file],
            'caption' => 'Test Caption',
            'alt_text' => 'Test Alt',
        ])
        ->assertDispatched('media-uploaded');

    $this->assertDatabaseHas('media_files', [
        'caption' => 'Test Caption',
    ]);

    $fileRecord = File::where('caption', 'Test Caption')->first();
    expect($fileRecord)->not->toBeNull('File record not found in database.');
    expect($fileRecord->name)->not->toBeEmpty('File name is empty.');
});

it('can select file in picker mode', function () {
    $file = File::create(['name' => 'Test File', 'uploaded_by_user_id' => 1]);
    $file->addMediaFromString('test content')
        ->usingFileName('test.txt')
        ->toMediaCollection('default');

    Livewire::test(MediaBrowser::class, ['isPicker' => true, 'pickerId' => 'test-picker', 'statePath' => 'data.uuids'])
        ->call('selectFile', $file->id)
        ->assertSet('selectedItems', ["file-{$file->id}"])
        ->assertDispatched('sync-picker-ids', function ($event, $params) use ($file) {
            return $params['statePath'] === 'data.uuids' && $params['ids'] === (string) $file->id;
        });
});

it('can select multiple files in picker mode', function () {
    $file1 = File::create(['name' => 'File 1', 'uploaded_by_user_id' => 1]);
    $file1->addMediaFromString('test 1')->usingFileName('test1.txt')->toMediaCollection('default');

    $file2 = File::create(['name' => 'File 2', 'uploaded_by_user_id' => 1]);
    $file2->addMediaFromString('test 2')->usingFileName('test2.txt')->toMediaCollection('default');

    Livewire::test(MediaBrowser::class, ['isPicker' => true, 'multiple' => true, 'pickerId' => 'test-picker', 'statePath' => 'data.uuids'])
        ->call('selectFile', $file1->id)
        ->call('selectFile', $file2->id)
        ->assertSet('selectedItems', ["file-{$file1->id}", "file-{$file2->id}"])
        ->assertDispatched('sync-picker-ids', function ($event, $params) use ($file1, $file2) {
            return $params['statePath'] === 'data.uuids' &&
                   $params['ids'] === "{$file1->id},{$file2->id}";
        });
});

test('view existence', function () {
    expect(view()->exists('media-manager::livewire.media-browser'))->toBeTrue('media-browser view missing');
    expect(view()->exists('media-manager::filament.pages.media-manager.pagination'))->toBeTrue('pagination view missing');
});

it('computes selection data', function () {
    $folder = Folder::create(['name' => 'Test Folder']);
    File::create(['name' => 'File 1', 'size' => 1024, 'folder_id' => $folder->id]);
    $file2 = File::create(['name' => 'File 2', 'size' => 2048]);

    Livewire::test(MediaBrowser::class)
        ->set('selectedItems', ["folder-{$folder->id}", "file-{$file2->id}"])
        ->assertSet('selectedItemsData', function ($data) {
            return $data['files_count'] === 2 &&
                   $data['folders_count'] === 1 &&
                   $data['size'] === 3072 &&
                   count($data['items'] ?? []) === 2;
        });
});

it('computes recursive folder stats', function () {
    $root = Folder::create(['name' => 'Root']);
    $sub = Folder::create(['name' => 'Sub', 'parent_id' => $root->id]);
    $deep = Folder::create(['name' => 'Deep', 'parent_id' => $root->id]); // siblings of sub

    File::create(['name' => 'Root File', 'size' => 100, 'folder_id' => $root->id]);
    File::create(['name' => 'Sub File', 'size' => 200, 'folder_id' => $sub->id]);
    File::create(['name' => 'Deep File', 'size' => 400, 'folder_id' => $deep->id]);

    $stats = $root->getRecursiveStats();

    expect($stats['files_count'])->toBe(3);
    expect($stats['folders_count'])->toBe(2);
    expect($stats['total_size'])->toBe(700);
});

it('can sort items by name', function () {
    Folder::create(['name' => 'B Folder']);
    Folder::create(['name' => 'A Folder']);
    File::create(['name' => 'C File']);

    Livewire::test(MediaBrowser::class)
        ->set('sortField', 'name')
        ->set('sortDirection', 'asc')
        ->assertSeeInOrder(['A Folder', 'B Folder', 'C File'])
        ->set('sortDirection', 'desc')
        ->assertSeeInOrder(['B Folder', 'A Folder', 'C File']); // Folders first ALWAYS
});

it('defaults to sorting by name when field is empty', function () {
    Folder::create(['name' => 'B Folder']);
    Folder::create(['name' => 'A Folder']);
    File::create(['name' => 'C File']);

    Livewire::test(MediaBrowser::class)
        // sortField is empty by default
        ->assertSeeInOrder(['A Folder', 'B Folder', 'C File']);
});

it('can sort items by size', function () {
    File::create(['name' => 'Large', 'size' => 5000]);
    File::create(['name' => 'Small', 'size' => 100]);
    Folder::create(['name' => 'Folder']); // Size 0 implicitly

    Livewire::test(MediaBrowser::class)
        ->set('sortField', 'size')
        ->set('sortDirection', 'asc')
        ->assertSeeInOrder(['Folder', 'Small', 'Large']) // Folder (0) -> Small -> Large
        ->set('sortDirection', 'desc')
        ->assertSeeInOrder(['Folder', 'Large', 'Small']); // Folders first ALWAYS
});

it('can sort items by date', function () {
    $old = Folder::create(['name' => 'Old']);
    $old->created_at = now()->subDay();
    $old->save();

    $new = File::create(['name' => 'New']);
    $new->created_at = now();
    $new->save();

    Livewire::test(MediaBrowser::class)
        ->set('sortField', 'created_at')
        ->set('sortDirection', 'asc')
        ->assertSeeInOrder(['Old', 'New'])
        ->set('sortDirection', 'desc')
        ->assertSeeInOrder(['Old', 'New']); // Folders first ALWAYS (Old is folder, New is file)
});

it('defaults to 10 items per page', function () {
    for ($i = 1; $i <= 13; $i++) {
        File::create(['name' => "File $i"]);
    }

    $component = Livewire::test(MediaBrowser::class);
    expect($component->get('perPage'))->toBe(10);

    $paginator = $component->get('items');
    expect($paginator->items())->toHaveCount(10);
    expect($paginator->total())->toBe(13);
});

it('can change per page', function () {
    for ($i = 1; $i <= 13; $i++) {
        File::create(['name' => "File $i"]);
    }

    Livewire::test(MediaBrowser::class)
        ->set('perPage', 25)
        ->assertCount('items', 13);
});

it('can show all items', function () {
    for ($i = 1; $i <= 13; $i++) {
        File::create(['name' => "File $i"]);
    }

    $component = Livewire::test(MediaBrowser::class)
        ->set('perPage', 'all');

    $paginator = $component->get('items');
    expect($paginator->items())->toHaveCount(13);
    expect($paginator->perPage())->toBe(13);
});

it('prevents selecting non accepted files in picker mode', function () {
    $user = User::create(['name' => 'Test User']);
    $pdfFile = File::create(['name' => 'PDF File', 'uploaded_by_user_id' => $user->id, 'mime_type' => 'application/pdf']);
    $jpgFile = File::create(['name' => 'JPG File', 'uploaded_by_user_id' => $user->id, 'mime_type' => 'image/jpeg']);

    Livewire::test(MediaBrowser::class, [
        'isPicker' => true,
        'acceptedFileTypes' => ['application/pdf'],
    ])
        ->call('selectFile', $pdfFile->id)
        ->assertSet('selectedItems', ["file-{$pdfFile->id}"])
        ->call('selectFile', $jpgFile->id)
        ->assertSet('selectedItems', ["file-{$pdfFile->id}"]); // JPG should NOT be selected
});

it('conditionally disables url syncing', function () {
    // Standalone mode: should have query string
    $standalone = new MediaBrowser;
    $standalone->isPicker = false;
    expect($standalone->queryString())->not->toBeEmpty();
    expect($standalone->queryString())->toHaveKey('sortField');

    // Picker mode: should have empty query string
    $picker = new MediaBrowser;
    $picker->isPicker = true;
    expect($picker->queryString())->toBeEmpty();
});
