<?php

namespace Slimani\MediaManager\Tables\Columns;

use Filament\Tables\Columns\Column;
use Slimani\MediaManager\Models\File;

class MediaColumn extends Column
{
    protected string $view = 'media-manager::filament.tables.columns.media-column';

    public function getMediaUrl(): ?string
    {
        $state = $this->getState();
        if (! $state) {
            return null;
        }

        $file = $state instanceof File ? $state : File::find($state);

        if ($file instanceof \Illuminate\Support\Collection) {
            $file = $file->first();
        }

        return $file instanceof File ? $file->getUrl('thumb') : null;
    }
}
