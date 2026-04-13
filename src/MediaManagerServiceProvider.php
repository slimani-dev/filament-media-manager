<?php

namespace Slimani\MediaManager;

use CodeWithDennis\FilamentSelectTree\SelectTree;
use Filament\Forms\Components\RichEditor\TipTapExtensions\ImageExtension;
use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Livewire\Livewire;
use Slimani\MediaManager\Form\RichEditor\Nodes\MediaImageNode;
use Slimani\MediaManager\Livewire\MediaBrowser;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class MediaManagerServiceProvider extends PackageServiceProvider
{
    public static string $name = 'media-manager';

    public function configurePackage(Package $package): void
    {
        $migrations = [
            'create_media_manager_tables',
        ];

        // Filter out migrations that have already been published
        $migrations = array_filter($migrations, function ($migration) {
            return ! file_exists(database_path("migrations/{$migration}.php")) &&
                   empty(glob(database_path("migrations/*_{$migration}.php")));
        });

        $package->name(static::$name)
            ->hasViews()
            ->hasTranslations()
            ->hasMigrations($migrations);
    }

    public function packageRegistered(): void
    {
        parent::packageRegistered();

        $this->app->bind(
            ImageExtension::class,
            MediaImageNode::class
        );
    }

    public function packageBooted(): void
    {
        Livewire::component('media-browser', MediaBrowser::class);

        $selectTreePath = dirname((new \ReflectionClass(SelectTree::class))->getFileName(), 2);

        FilamentAsset::register([
            Css::make('media-manager-styles', __DIR__.'/../resources/css/media-manager.css')->loadedOnRequest(),
            AlpineComponent::make('filament-select-tree', $selectTreePath.'/resources/dist/filament-select-tree.js')->loadedOnRequest(),
            Css::make('filament-select-tree-styles', $selectTreePath.'/resources/dist/filament-select-tree.css')->loadedOnRequest(),
        ], 'slimani/media-manager');
    }
}
