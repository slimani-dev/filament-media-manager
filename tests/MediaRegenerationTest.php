<?php

namespace Slimani\MediaManager\Tests;

use Filament\Actions\Action;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use Slimani\MediaManager\Pages\MediaManager;

uses(TestCase::class);

it('renders media manager page with regenerate action group', function () {
    Livewire::test(MediaManager::class)
        ->assertStatus(200)
        ->assertSee(__('Regenerate Conversions'));
});

it('can call regenerate conversions action', function () {
    // Instead of mocking Artisan (which hits final classes issues in testbench),
    // we just verify the action runs and notifies success.

    Livewire::test(MediaManager::class)
        ->callAction('regenerate_conversions', [
            'ids' => [],
            'conversions' => [],
            'only_missing' => true,
            'force' => false,
            'with_responsive_images' => false,
        ])
        ->assertNotified();
});
