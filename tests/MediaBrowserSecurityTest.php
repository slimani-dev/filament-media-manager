<?php

namespace Slimani\MediaManager\Tests;

use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Slimani\MediaManager\Livewire\MediaBrowser;

uses(TestCase::class);

class UnserializeProbe
{
    public static bool $woken = false;

    public function __wakeup(): void
    {
        static::$woken = true;
    }
}

beforeEach(function () {
    UnserializeProbe::$woken = false;
});

it('never unserializes an onSelect payload passed to mount', function () {
    Livewire::test(MediaBrowser::class, [
        'isPicker' => true,
        'onSelect' => serialize(new UnserializeProbe),
    ])->call('clearSelection');

    expect(UnserializeProbe::$woken)->toBeFalse();
});

it('does not expose a client-writable serialized callback property', function () {
    expect(property_exists(MediaBrowser::class, 'serializedOnSelect'))->toBeFalse();

    try {
        Livewire::test(MediaBrowser::class)
            ->set('serializedOnSelect', serialize(new UnserializeProbe))
            ->call('clearSelection');
    } catch (\Throwable) {
        // Livewire refuses to set an unknown property; either way nothing may be unserialized.
    }

    expect(UnserializeProbe::$woken)->toBeFalse();
});

it('locks the picker configuration against client updates', function (string $property, mixed $value) {
    Livewire::test(MediaBrowser::class, ['isPicker' => true, 'statePath' => 'data.media'])
        ->set($property, $value);
})->with([
    'isPicker' => ['isPicker', false],
    'pickerId' => ['pickerId', 'other'],
    'statePath' => ['statePath', 'data.other'],
    'acceptedFileTypes' => ['acceptedFileTypes', []],
])->throws(CannotUpdateLockedPropertyException::class);
