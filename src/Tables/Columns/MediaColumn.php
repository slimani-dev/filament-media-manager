<?php

namespace Slimani\MediaManager\Tables\Columns;

use Filament\Tables\Columns\ImageColumn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Slimani\MediaManager\Models\File;

class MediaColumn extends ImageColumn
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
        $state ??= $this->getState();

        if ($state instanceof Collection) {
            $state = $state->first();
        }

        return ($state instanceof File) ? $state->getUrl($this->getConversion()) : null;
    }
}
