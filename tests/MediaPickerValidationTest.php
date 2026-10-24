<?php

namespace Slimani\MediaManager\Tests;

use Livewire\Livewire;
use Slimani\MediaManager\Models\File;
use Slimani\MediaManager\Tests\Components\TestMediaPickerValidationForm;

uses(TestCase::class);

it('enforces required', function () {
    Livewire::test(TestMediaPickerValidationForm::class)
        ->call('submit')
        ->assertHasErrors(['data.avatar_id' => 'required']);
});

it('accepts a picked file id, even with image() rules', function () {
    $file = File::create(['name' => 'Avatar']);

    Livewire::test(TestMediaPickerValidationForm::class)
        ->fillForm(['avatar_id' => (string) $file->id])
        ->call('submit')
        ->assertHasNoErrors();
});

it('enforces maxFiles on multiple pickers', function () {
    $ids = collect(range(1, 3))->map(fn (int $i): string => (string) File::create(['name' => "Doc {$i}"])->id)->all();
    $avatar = File::create(['name' => 'Avatar']);

    Livewire::test(TestMediaPickerValidationForm::class)
        ->fillForm(['avatar_id' => (string) $avatar->id, 'documents' => $ids])
        ->call('submit')
        ->assertHasErrors(['data.documents' => 'max']);
});

it('runs custom rules', function () {
    $avatar = File::create(['name' => 'Avatar']);

    Livewire::test(TestMediaPickerValidationForm::class)
        ->fillForm(['avatar_id' => (string) $avatar->id, 'contract_id' => '999'])
        ->call('submit')
        ->assertHasErrors(['data.contract_id']);
});
