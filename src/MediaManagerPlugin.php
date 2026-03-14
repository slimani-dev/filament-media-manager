<?php

namespace Slimani\MediaManager;

use Closure;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Illuminate\Contracts\View\View;
use Slimani\MediaManager\Pages\MediaManager;

class MediaManagerPlugin implements Plugin
{
    use EvaluatesClosures;

    protected string|Closure $page = MediaManager::class;

    protected string|Closure|null $disk = null;

    protected string|Closure $navigationGroup = 'Content';

    protected string|Closure $navigationLabel = 'Media Manager';

    protected string|Closure|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected int|Closure|null $navigationSort = null;

    protected bool|Closure $shouldRegisterNavigation = true;

    protected array|Closure $headerWidgets = [];

    protected array|Closure $footerWidgets = [];

    protected View|Closure|null $header = null;

    protected View|Closure|null $footer = null;

    public function getId(): string
    {
        return 'media-manager';
    }

    public function register(Panel $panel): void
    {
        $panel->pages([
            $this->getPage(),
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

    /*
     * Customize the Media Manager page or replace it entirely.
     *
     * @param  string|Closure  $page
     */
    public function mediaManagerPage(string|Closure $page): static
    {
        $this->page = $page;

        return $this;
    }

    public function getPage(): string
    {
        return (string) $this->evaluate($this->page);
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

    public function navigationGroup(string|Closure $group): static
    {
        $this->navigationGroup = $group;

        return $this;
    }

    public function getNavigationGroup(): string
    {
        return $this->evaluate($this->navigationGroup);
    }

    public function navigationLabel(string|Closure $label): static
    {
        $this->navigationLabel = $label;

        return $this;
    }

    public function getNavigationLabel(): string
    {
        return $this->evaluate($this->navigationLabel);
    }

    public function navigationIcon(string|Closure|\BackedEnum|null $icon): static
    {
        $this->navigationIcon = $icon;

        return $this;
    }

    public function getNavigationIcon(): string|\BackedEnum|null
    {
        return $this->evaluate($this->navigationIcon);
    }

    public function navigationSort(int|Closure|null $sort): static
    {
        $this->navigationSort = $sort;

        return $this;
    }

    public function getNavigationSort(): ?int
    {
        return $this->evaluate($this->navigationSort);
    }

    public function shouldRegisterNavigation(bool|Closure $condition = true): static
    {
        $this->shouldRegisterNavigation = $condition;

        return $this;
    }

    public function getShouldRegisterNavigation(): bool
    {
        return (bool) $this->evaluate($this->shouldRegisterNavigation);
    }

    public function headerWidgets(array|Closure $widgets): static
    {
        $this->headerWidgets = $widgets;

        return $this;
    }

    public function getHeaderWidgets(): array
    {
        return $this->evaluate($this->headerWidgets);
    }

    public function footerWidgets(array|Closure $widgets): static
    {
        $this->footerWidgets = $widgets;

        return $this;
    }

    public function getFooterWidgets(): array
    {
        return $this->evaluate($this->footerWidgets);
    }

    public function header(View|Closure|null $header): static
    {
        $this->header = $header;

        return $this;
    }

    public function getHeader(): ?View
    {
        return $this->evaluate($this->header);
    }

    public function footer(View|Closure|null $footer): static
    {
        $this->footer = $footer;

        return $this;
    }

    public function getFooter(): ?View
    {
        return $this->evaluate($this->footer);
    }
}
