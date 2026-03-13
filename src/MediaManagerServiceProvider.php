<?php

namespace Slimani\MediaManager;

use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Livewire\Livewire;
use Slimani\MediaManager\Livewire\MediaBrowser;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class MediaManagerServiceProvider extends PackageServiceProvider
{
    public static string $name = 'media-manager';

    public function configurePackage(Package $package): void
    {
        $migrations = [
            'create_media_folders_table',
            'create_media_files_table',
            'add_name_to_media_files_table',
            'create_media_tags_table',
        ];

        // Filter out migrations that have already been published
        $migrations = array_filter($migrations, function ($migration) {
            return ! file_exists(database_path("migrations/{$migration}.php")) &&
                   empty(glob(database_path("migrations/*_{$migration}.php")));
        });

        $package->name(static::$name)
            ->hasViews()
            ->hasMigrations($migrations);
    }

    public function packageBooted(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        Livewire::component('media-browser', MediaBrowser::class);

        FilamentAsset::register([
            Css::make('media-manager-styles', __DIR__.'/../resources/css/media-manager.css')->loadedOnRequest(),
        ], 'slimani/media-manager');
    }
}
