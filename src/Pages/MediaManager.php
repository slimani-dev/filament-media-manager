<?php

namespace Slimani\MediaManager\Pages;

use Composer\InstalledVersions;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Facades\Filament;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Size;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Slimani\MediaManager\MediaManagerPlugin;

class MediaManager extends Page implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms, InteractsWithSchemas {
        InteractsWithForms::getCachedSchemas insteadof InteractsWithSchemas;
        InteractsWithSchemas::getCachedSchemas as getBaseCachedSchemas;
    }

    protected string $view = 'media-manager::filament.pages.media-manager';

    public static function getNavigationGroup(): ?string
    {
        return static::getPlugin()->getNavigationGroup();
    }

    public static function getNavigationLabel(): string
    {
        return static::getPlugin()->getNavigationLabel();
    }

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return static::getPlugin()->getNavigationIcon();
    }

    public static function getNavigationSort(): ?int
    {
        return static::getPlugin()->getNavigationSort();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::getPlugin()->getShouldRegisterNavigation();
    }

    public function getHeaderWidgets(): array
    {
        return static::getPlugin()->getHeaderWidgets();
    }

    public function getFooterWidgets(): array
    {
        return static::getPlugin()->getFooterWidgets();
    }

    public function getHeader(): ?View
    {
        return static::getPlugin()->getHeader();
    }

    public function getFooter(): ?View
    {
        return static::getPlugin()->getFooter();
    }

    protected static function getPlugin(): MediaManagerPlugin
    {
        return Filament::getPlugin('media-manager');
    }

    protected function getHeaderActions(): array
    {
        $versionInfo = $this->getVersionInfo();
        $version = $versionInfo['version'];
        $hasUpdate = $versionInfo['hasUpdate'];

        return [
            Action::make('version')
                ->label($hasUpdate ? "Update available: {$versionInfo['latestVersion']}" : $version)
                ->icon($hasUpdate ? 'heroicon-m-arrow-path' : null)
                ->url('https://github.com/slimani-dev/filament-media-manager/releases', true)
                ->link()
                ->size(Size::ExtraSmall)
                ->color($hasUpdate ? Color::Red : Color::Gray)
                ->tooltip($hasUpdate ? "Current version {$version}" : 'up to date ✅'),
        ];
    }

    protected function getVersionInfo(): array
    {
        $packageName = 'slimani/filament-media-manager';

        $installedVersion = 'v0.0.0';
        try {
            $installedVersion = InstalledVersions::getPrettyVersion($packageName) ?? 'v0.0.0';
        } catch (\Exception $e) {
            // Fallback
        }

        $cacheKey = 'media_manager_latest_version';

        $latestVersion = Cache::remember($cacheKey, now()->addDay(), function () {
            try {
                $response = Http::get('https://api.github.com/repos/slimani-dev/filament-media-manager/releases/latest');

                if ($response->successful()) {
                    return $response->json('tag_name');
                }
            } catch (\Exception $e) {
                // Ignore network errors
            }

            return null;
        });

        $hasUpdate = false;
        if ($latestVersion && $installedVersion !== 'dev-main') {
            $hasUpdate = version_compare(
                ltrim($installedVersion, 'v'),
                ltrim($latestVersion, 'v'),
                '<'
            );
        }

        return [
            'version' => $installedVersion,
            'latestVersion' => $latestVersion,
            'hasUpdate' => $hasUpdate,
        ];
    }
}
