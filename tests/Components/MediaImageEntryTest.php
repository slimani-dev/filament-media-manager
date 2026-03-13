<?php

use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Blade;
use Livewire\Component;
use Slimani\MediaManager\Infolists\Components\MediaImageEntry;
use Slimani\MediaManager\Models\File;
use Slimani\MediaManager\Tests\TestCase;

uses(TestCase::class);

it('can render an image file', function () {
    $file = File::create([
        'name' => 'test-image',
        'mime_type' => 'image/jpeg',
        'size' => 1024,
        'extension' => 'jpg',
    ]);

    $dummyLivewire = new class extends Component implements HasSchemas
    {
        use InteractsWithSchemas;
    };
    $component = MediaImageEntry::make('file')
        ->container(Schema::make($dummyLivewire)->record($file))
        ->state($file->id)
        ->circular();

    $html = Blade::render($component->toHtml());

    expect($html)
        ->toContain('test-image')
        ->toContain('fi-in-image')
        ->toContain('rounded-full');
});

it('can render a non-image file thumbnail', function () {
    $file = File::create([
        'name' => 'test-pdf',
        'mime_type' => 'application/pdf',
        'size' => 2048,
        'extension' => 'pdf',
    ]);

    $dummyLivewire = new class extends Component implements HasSchemas
    {
        use InteractsWithSchemas;
    };
    $component = MediaImageEntry::make('file')
        ->container(Schema::make($dummyLivewire)->record($file))
        ->state($file->id);

    $html = Blade::render($component->toHtml());

    expect($html)
        ->toContain('fi-in-document-text')
        ->toContain('2 KB')
        ->toContain('<svg');
});

it('can render multiple files', function () {
    $file1 = File::create([
        'name' => 'image1.jpg',
        'mime_type' => 'image/jpeg',
        'disk' => 'public',
        'path' => 'media/image1.jpg',
        'size' => 1024,
        'extension' => 'jpg',
    ]);
    $file2 = File::create([
        'name' => 'doc1.pdf',
        'mime_type' => 'application/pdf',
        'disk' => 'public',
        'path' => 'media/doc1.pdf',
        'size' => 2048,
        'extension' => 'pdf',
    ]);

    $dummyLivewire = new class extends Component implements HasSchemas
    {
        use InteractsWithSchemas;
    };
    $component = MediaImageEntry::make('files')
        ->container(Schema::make($dummyLivewire)->record($file1))
        ->state([$file1->id, $file2->id]);

    $html = Blade::render($component->toHtml());

    expect($html)
        ->toContain('image1.jpg')
        ->toContain('doc1.pdf')
        ->toContain('2 KB');
});

it('can handle File model in state', function () {
    $file = File::create([
        'name' => 'model-file.jpg',
        'mime_type' => 'image/jpeg',
        'disk' => 'public',
        'path' => 'media/model-file.jpg',
        'size' => 1024,
        'extension' => 'jpg',
    ]);

    $dummyLivewire = new class extends Component implements HasSchemas
    {
        use InteractsWithSchemas;
    };
    $component = MediaImageEntry::make('file')
        ->container(Schema::make($dummyLivewire)->record($file))
        ->state($file);

    $html = Blade::render($component->toHtml());

    expect($html)->toContain('model-file.jpg');
});

it('can handle Collection of File models in state', function () {
    $file1 = File::create([
        'name' => 'file1.jpg',
        'mime_type' => 'image/jpeg',
        'disk' => 'public',
        'path' => 'media/file1.jpg',
        'size' => 1024,
        'extension' => 'jpg',
    ]);
    $file2 = File::create([
        'name' => 'file2.jpg',
        'mime_type' => 'image/jpeg',
        'disk' => 'public',
        'path' => 'media/file2.jpg',
        'size' => 1024,
        'extension' => 'jpg',
    ]);

    $dummyLivewire = new class extends Component implements HasSchemas
    {
        use InteractsWithSchemas;
    };
    $component = MediaImageEntry::make('files')
        ->container(Schema::make($dummyLivewire)->record($file1))
        ->state(collect([$file1, $file2]));

    $html = Blade::render($component->toHtml());

    expect($html)
        ->toContain('file1.jpg')
        ->toContain('file2.jpg');
});
