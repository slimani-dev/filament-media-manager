<?php

namespace Slimani\MediaManager\Infolists\Components;

use Filament\Infolists\Components\ImageEntry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Slimani\MediaManager\MediaManagerPlugin;

class MediaImageEntry extends ImageEntry
{
    protected string|\Closure|null $conversion = null;

    public function conversion(string|\Closure|null $name): static
    {
        $this->conversion = $name;

        return $this;
    }

    public function getConversion(): ?string
    {
        return $this->evaluate($this->conversion) ?? 'thumb';
    }

    /**
     * Filament 5.10 added `$relatedRecord`; declaring it keeps the signature compatible with both v4 and v5.
     */
    public function getImageUrl(mixed $state = null, ?Model $relatedRecord = null): ?string
    {
        if (blank($state)) {
            $state = $this->getState();
        }

        if ($state instanceof Collection) {
            $state = $state->first();
        }

        if (is_numeric($state)) {
            /** @var MediaManagerPlugin $plugin */
            $plugin = filament('media-manager');
            $fileModel = $plugin->getFileModel();
            $state = $fileModel::find($state);
        }

        if ($state instanceof ($this->getFileModel())) {
            return $state->getUrl($this->getConversion());
        }

        // Filament 4's parent takes only the state; PHP ignores the extra argument there.
        return parent::getImageUrl($state, $relatedRecord);
    }

    protected function getFileModel(): string
    {
        /** @var MediaManagerPlugin $plugin */
        $plugin = filament('media-manager');

        return $plugin->getFileModel();
    }
}
