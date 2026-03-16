<?php

namespace Slimani\MediaManager\Tables\Columns;

use Filament\Tables\Columns\ImageColumn;
use Illuminate\Support\Collection;
use Slimani\MediaManager\Models\File;

class MediaColumn extends ImageColumn
{
    public function getImageUrl(mixed $state = null): ?string
    {
        $state ??= $this->getState();

        if ($state instanceof Collection) {
            $state = $state->first();
        }

        return ($state instanceof File) ? $state->getUrl('thumb') : null;
    }
}
