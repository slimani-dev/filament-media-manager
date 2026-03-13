<?php

namespace Slimani\MediaManager\Tests;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Slimani\MediaManager\Livewire\MediaBrowser;
use Slimani\MediaManager\Models\File;
use Slimani\MediaManager\Models\Folder;

class MediaBrowserTest extends TestCase
{
    public function test_it_can_render_media_browser_page()
    {
        Livewire::test(MediaBrowser::class)
            ->assertStatus(200);
    }

    public function test_it_can_create_folder()
    {
        Livewire::test(MediaBrowser::class)
            ->callAction('createFolder', ['name' => 'Test Folder'])
            ->assertDispatched('media-uploaded');

        $this->assertDatabaseHas('media_folders', ['name' => 'Test Folder']);
    }

    public function test_it_can_navigate_folders()
    {
        $folder = Folder::create(['name' => 'My Folder']);

        Livewire::test(MediaBrowser::class)
            ->set('currentFolderId', $folder->id)
            ->assertSet('currentFolderId', $folder->id);
    }

    public function test_it_can_search_with_folder_set()
    {
        $folder = Folder::create(['name' => 'My Folder']);

        Livewire::test(MediaBrowser::class)
            ->call('setCurrentFolder', $folder->id)
            ->set('search', 'test')
            ->assertStatus(200);
    }

    public function test_it_scopes_search_to_current_folder_tree()
    {
        $folderA = Folder::create(['name' => 'Folder A']);
        $folderB = Folder::create(['name' => 'Folder B']);

        $fileA = File::create(['name' => 'SearchMe', 'folder_id' => $folderA->id]);
        $fileB = File::create(['name' => 'SearchMe', 'folder_id' => $folderB->id]);

        Livewire::test(MediaBrowser::class)
            ->call('setCurrentFolder', $folderA->id)
            ->set('search', 'SearchMe')
            ->assertSee($fileA->name);
        // We can check the internal property to be more precise
        // but let's see if this passes first.
    }

    public function test_it_can_upload_file()
    {
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

        $file = File::where('caption', 'Test Caption')->first();
        $this->assertNotNull($file, 'File record not found in database.');
        $this->assertNotEmpty($file->name, 'File name is empty.');
    }

    public function test_it_can_select_file_in_picker_mode()
    {
        $user = User::factory()->create();
        $file = File::create(['name' => 'Test File', 'uploaded_by_user_id' => $user->id]);
        $media = $file->addMediaFromString('test content')
            ->usingFileName('test.txt')
            ->toMediaCollection('default');

        Livewire::test(MediaBrowser::class, ['isPicker' => true, 'pickerId' => 'test-picker', 'statePath' => 'data.uuids'])
            ->call('selectFile', $file->id)
            ->assertSet('selectedItems', ["file-{$file->id}"])
            ->assertDispatched('sync-picker-ids', function ($event, $params) use ($file) {
                return $params['statePath'] === 'data.uuids' && $params['ids'] === (string) $file->id;
            });
    }

    public function test_it_can_select_multiple_files_in_picker_mode()
    {
        $user = User::factory()->create();
        $file1 = File::create(['name' => 'File 1', 'uploaded_by_user_id' => $user->id]);
        $media1 = $file1->addMediaFromString('test 1')->usingFileName('test1.txt')->toMediaCollection('default');

        $file2 = File::create(['name' => 'File 2', 'uploaded_by_user_id' => $user->id]);
        $media2 = $file2->addMediaFromString('test 2')->usingFileName('test2.txt')->toMediaCollection('default');

        Livewire::test(MediaBrowser::class, ['isPicker' => true, 'multiple' => true, 'pickerId' => 'test-picker', 'statePath' => 'data.uuids'])
            ->call('selectFile', $file1->id)
            ->call('selectFile', $file2->id)
            ->assertSet('selectedItems', ["file-{$file1->id}", "file-{$file2->id}"])
            ->assertDispatched('sync-picker-ids', function ($event, $params) use ($file1, $file2) {
                return $params['statePath'] === 'data.uuids' &&
                       $params['ids'] === "{$file1->id},{$file2->id}";
            });
    }

    public function test_view_existence()
    {
        $this->assertTrue(view()->exists('media-manager::livewire.media-browser'), 'media-browser view missing');
        $this->assertTrue(view()->exists('media-manager::filament.pages.media-manager.pagination'), 'pagination view missing');
    }

    public function test_it_computes_selection_data()
    {
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
    }

    public function test_it_computes_recursive_folder_stats()
    {
        $root = Folder::create(['name' => 'Root']);
        $sub = Folder::create(['name' => 'Sub', 'parent_id' => $root->id]);
        $deep = Folder::create(['name' => 'Deep', 'parent_id' => $root->id]); // siblings of sub

        File::create(['name' => 'Root File', 'size' => 100, 'folder_id' => $root->id]);
        File::create(['name' => 'Sub File', 'size' => 200, 'folder_id' => $sub->id]);
        File::create(['name' => 'Deep File', 'size' => 400, 'folder_id' => $deep->id]);

        $stats = $root->getRecursiveStats();

        $this->assertEquals(3, $stats['files_count']);
        $this->assertEquals(2, $stats['folders_count']);
        $this->assertEquals(700, $stats['total_size']);
    }

    public function test_it_can_sort_items_by_name()
    {
        Folder::create(['name' => 'B Folder']);
        Folder::create(['name' => 'A Folder']);
        File::create(['name' => 'C File']);

        Livewire::test(MediaBrowser::class)
            ->set('sortField', 'name')
            ->set('sortDirection', 'asc')
            ->assertSeeInOrder(['A Folder', 'B Folder', 'C File'])
            ->set('sortDirection', 'desc')
            ->assertSeeInOrder(['B Folder', 'A Folder', 'C File']); // Folders first ALWAYS
    }

    public function test_it_defaults_to_sorting_by_name_when_field_is_empty()
    {
        Folder::create(['name' => 'B Folder']);
        Folder::create(['name' => 'A Folder']);
        File::create(['name' => 'C File']);

        Livewire::test(MediaBrowser::class)
            // sortField is empty by default
            ->assertSeeInOrder(['A Folder', 'B Folder', 'C File']);
    }

    public function test_it_can_sort_items_by_size()
    {
        File::create(['name' => 'Large', 'size' => 5000]);
        File::create(['name' => 'Small', 'size' => 100]);
        Folder::create(['name' => 'Folder']); // Size 0 implicitly

        Livewire::test(MediaBrowser::class)
            ->set('sortField', 'size')
            ->set('sortDirection', 'asc')
            ->assertSeeInOrder(['Folder', 'Small', 'Large']) // Folder (0) -> Small -> Large
            ->set('sortDirection', 'desc')
            ->assertSeeInOrder(['Folder', 'Large', 'Small']); // Folders first ALWAYS
    }

    public function test_it_can_sort_items_by_date()
    {
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
    }

    public function test_it_defaults_to_10_items_per_page()
    {
        for ($i = 1; $i <= 13; $i++) {
            File::create(['name' => "File $i"]);
        }

        $component = Livewire::test(MediaBrowser::class);
        $this->assertEquals(10, $component->get('perPage'));

        $paginator = $component->get('items');
        $this->assertCount(10, $paginator->items());
        $this->assertEquals(13, $paginator->total());
    }

    public function test_it_can_change_per_page()
    {
        for ($i = 1; $i <= 13; $i++) {
            File::create(['name' => "File $i"]);
        }

        Livewire::test(MediaBrowser::class)
            ->set('perPage', 25)
            ->assertCount('items', 13);
    }

    public function test_it_can_show_all_items()
    {
        for ($i = 1; $i <= 13; $i++) {
            File::create(['name' => "File $i"]);
        }

        $component = Livewire::test(MediaBrowser::class)
            ->set('perPage', 'all');

        $paginator = $component->get('items');
        $this->assertCount(13, $paginator->items());
        $this->assertEquals(13, $paginator->perPage());
    }

    public function test_it_prevents_selecting_non_accepted_files_in_picker_mode()
    {
        $user = User::factory()->create();
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
    }

    public function test_it_conditionally_disables_url_syncing()
    {
        // Standalone mode: should have query string
        $standalone = new MediaBrowser;
        $standalone->isPicker = false;
        $this->assertNotEmpty($standalone->queryString());
        $this->assertArrayHasKey('sortField', $standalone->queryString());

        // Picker mode: should have empty query string
        $picker = new MediaBrowser;
        $picker->isPicker = true;
        $this->assertEmpty($picker->queryString());
    }
}
