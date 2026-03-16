<?php

namespace Slimani\MediaManager\Tests\Components;

use Filament\Tables\Columns\ImageColumn;
use Slimani\MediaManager\Models\File;
use Slimani\MediaManager\Tables\Columns\MediaColumn;
use Slimani\MediaManager\Tests\TestCase;
use Illuminate\Support\Facades\Storage;

uses(TestCase::class);

beforeEach(function () {
    Storage::fake('public');
});

it('extends ImageColumn', function () {
    $column = MediaColumn::make('test');
    
    expect($column)->toBeInstanceOf(ImageColumn::class);
});


it('returns correct image url for File model', function () {
    $file = File::create([
        'name' => 'test-image-model',
        'mime_type' => 'image/jpeg',
        'size' => 1024,
        'extension' => 'jpg',
    ]);
    $file->addMediaFromString('test content')->toMediaCollection('default');

    $column = MediaColumn::make('file');
    $url = $column->getImageUrl($file);

    expect($url)->not->toBeNull();
});

it('returns correct image url for collection of files', function () {
    $file = File::create([
        'name' => 'test-image-col',
        'mime_type' => 'image/jpeg',
        'size' => 1024,
        'extension' => 'jpg',
    ]);
    $file->addMediaFromString('test content')->toMediaCollection('default');

    $column = MediaColumn::make('files');
    $url = $column->getImageUrl(collect([$file]));

    expect($url)->not->toBeNull();
});


it('returns null for empty state', function () {
    $column = MediaColumn::make('file');
    $url = $column->getImageUrl(null);

    expect($url)->toBeNull();
});
