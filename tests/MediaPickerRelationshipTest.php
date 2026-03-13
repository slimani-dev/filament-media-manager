<?php

namespace Slimani\MediaManager\Tests;

use Livewire\Livewire;
use Slimani\MediaManager\Models\File;
use Slimani\MediaManager\Tests\Components\TestMediaPickerRelationshipForm;
use Slimani\MediaManager\Tests\Models\User;

uses(TestCase::class);

it('hydrates id from integer id', function () {
    $file = File::create(['name' => 'Avatar']);

    $user = User::create(['name' => 'Test User', 'avatar_id' => $file->id]);

    Livewire::actingAs($user)
        ->test(TestMediaPickerRelationshipForm::class, ['user' => $user])
        ->assertSet('data.avatar_id', fn ($state) => (int) $state === (int) $file->id);
});

it('dehydrates integer id', function () {
    $file = File::create(['name' => 'Avatar']);

    $user = User::create(['name' => 'Test User']);

    Livewire::actingAs($user)
        ->test(TestMediaPickerRelationshipForm::class, ['user' => $user])
        ->fillForm(['avatar_id' => $file->id])
        ->call('submit');

    expect($user->fresh()->avatar_id)->toBe($file->id);
});

it('handles multiple relationship mapping', function () {
    $file1 = File::create(['name' => 'Doc 1']);
    $file2 = File::create(['name' => 'Doc 2']);

    $user = User::create(['name' => 'Test User']);

    Livewire::actingAs($user)
        ->test(TestMediaPickerRelationshipForm::class, ['user' => $user])
        ->fillForm(['documents' => [$file1->id, $file2->id]])
        ->call('submit');

    expect($user->fresh()->documents)->toHaveCount(2);
    expect($user->fresh()->documents->pluck('id'))->toContain($file1->id, $file2->id);
});
