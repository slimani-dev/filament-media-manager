<?php

namespace Slimani\MediaManager;

use Closure;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Slimani\MediaManager\Pages\MediaManager;

class MediaManagerPlugin implements Plugin
{
    use EvaluatesClosures;

    protected string|Closure|null $disk = null;

    public function getId(): string
    {
        return 'media-manager';
    }

    public function register(Panel $panel): void
    {
        $panel->pages([
            MediaManager::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public function disk(string|Closure $disk): static
    {
        $this->disk = $disk;

        return $this;
    }

    public function getDisk(): string
    {
        return $this->evaluate($this->disk) ?? config('media-library.disk_name', 'media');
    }
}
