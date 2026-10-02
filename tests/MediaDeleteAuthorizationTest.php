<?php

namespace Slimani\MediaManager\Tests;

use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Slimani\MediaManager\Livewire\MediaBrowser;
use Slimani\MediaManager\Models\File;
use Slimani\MediaManager\Models\Folder;
use Slimani\MediaManager\Tests\Models\User;

uses(TestCase::class);

/**
 * Only lets users delete files they uploaded, and no folders.
 */
class OwnFilesOnlyPolicy
{
    public function delete(User $user, File|Folder $item): bool
    {
        return $item instanceof File && (int) $item->uploaded_by_user_id === (int) $user->getKey();
    }
}

beforeEach(function () {
    $this->user = User::create(['name' => 'Writer', 'email' => 'writer@example.com', 'password' => 'secret']);
    $this->actingAs($this->user);
});

it('deletes as before when the app registers no policy', function () {
    $file = File::create(['name' => 'Anyone', 'uploaded_by_user_id' => 999]);

    Livewire::test(MediaBrowser::class)->call('deleteFile', $file->id);

    expect(File::find($file->id))->toBeNull();
});

it('asks the registered policy before deleting a file', function () {
    Gate::policy(File::class, OwnFilesOnlyPolicy::class);
    $own = File::create(['name' => 'Mine', 'uploaded_by_user_id' => $this->user->id]);
    $other = File::create(['name' => 'Theirs', 'uploaded_by_user_id' => 999]);

    Livewire::test(MediaBrowser::class)
        ->call('deleteFile', $other->id)
        ->assertNotified()
        ->call('deleteFile', $own->id);

    expect(File::find($other->id))->not->toBeNull()
        ->and(File::find($own->id))->toBeNull();
});

it('skips the selected items the user may not delete', function () {
    Gate::policy(File::class, OwnFilesOnlyPolicy::class);
    Gate::policy(Folder::class, OwnFilesOnlyPolicy::class);
    $own = File::create(['name' => 'Mine', 'uploaded_by_user_id' => $this->user->id]);
    $other = File::create(['name' => 'Theirs', 'uploaded_by_user_id' => 999]);
    $folder = Folder::create(['name' => 'Shared']);

    Livewire::test(MediaBrowser::class)
        ->set('selectedItems', ["file-{$own->id}", "file-{$other->id}", "folder-{$folder->id}"])
        ->call('deleteSelectedItems');

    expect(File::find($own->id))->toBeNull()
        ->and(File::find($other->id))->not->toBeNull()
        ->and(Folder::find($folder->id))->not->toBeNull();
});

it('hides bulk delete when nothing selected can be deleted', function () {
    Gate::policy(File::class, OwnFilesOnlyPolicy::class);
    $other = File::create(['name' => 'Theirs', 'uploaded_by_user_id' => 999]);

    Livewire::test(MediaBrowser::class)
        ->set('selectedItems', ["file-{$other->id}"])
        ->assertActionHidden('bulkDelete');
});
