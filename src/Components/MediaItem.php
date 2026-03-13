<?php

namespace Slimani\MediaManager\Components;

use Closure;
use Filament\Schemas\Components\Component;
use Slimani\MediaManager\Models\File;
use Slimani\MediaManager\Models\Folder;

class MediaItem extends Component
{
    protected string $view = 'media-manager::filament.components.media-item';

    protected File|Folder $record;

    protected bool $isAccepted = true;

    protected bool $isPicker = false;

    public static function make(File|Folder $record): static
    {
        $static = app(static::class);
        $static->record($record);
        $static->configure();

        return $static;
    }

    public function record(File|Folder $record): static
    {
        $this->record = $record;

        return $this;
    }

    public function isAccepted(bool|Closure $isAccepted = true): static
    {
        $this->isAccepted = $isAccepted;

        return $this;
    }

    public function isPicker(bool|Closure $isPicker = false): static
    {
        $this->isPicker = $isPicker;

        return $this;
    }

    public function getIsAccepted(): bool
    {
        return (bool) $this->evaluate($this->isAccepted);
    }

    public function getIsPicker(): bool
    {
        return (bool) $this->evaluate($this->isPicker);
    }

    public function getRecord(bool $withContainerRecord = true): File|Folder|null
    {
        return $this->record;
    }

    public function getExtraViewData(): array
    {
        return [
            ...parent::getExtraViewData(),
            'item' => $this->getRecord(),
            'isAccepted' => $this->getIsAccepted(),
            'isPicker' => $this->getIsPicker(),
        ];
    }
}
