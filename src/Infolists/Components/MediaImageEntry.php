<?php

namespace Slimani\MediaManager\Infolists\Components;

use Filament\Infolists\Components\ImageEntry;
use Hugomyb\FilamentMediaAction\Actions\MediaAction;
use Illuminate\Support\Collection;
use Slimani\MediaManager\Models\File;

class MediaImageEntry extends ImageEntry
{
    protected string $view = 'media-manager::infolists.components.media-image-entry';

    protected string|\Closure|null $conversion = null;

    public function conversion(string|\Closure|null $name): static
    {
        $this->conversion = $name;

        return $this;
    }

    public function getConversion(): ?string
    {
        return $this->evaluate($this->conversion) ?? '';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->action(
            MediaAction::make('preview')
                ->slideOver()
                ->media(fn (array $arguments) => $arguments['url'] ?? null),
        );
    }

    public function getFileRecords(): Collection
    {
        $state = $this->getState();

        if (blank($state)) {
            return collect();
        }

        // If it's already a collection or array of models
        if ($state instanceof Collection) {
            return $state->filter(fn ($item) => $item instanceof File);
        }

        if ($state instanceof File) {
            return collect([$state]);
        }

        $ids = is_array($state) ? $state : [$state];

        // If the array contains models instead of IDs
        if (isset($ids[0]) && $ids[0] instanceof File) {
            return collect($ids);
        }

        // Ensure we only have numeric IDs
        $ids = array_filter($ids, fn ($id) => is_numeric($id));

        if (empty($ids)) {
            return collect();
        }

        return File::whereIn('id', $ids)
            ->get()
            ->sortBy(fn ($file) => array_search((string) $file->id, array_map('strval', $ids)));
    }

    public function getFileRecord(): ?File
    {
        return $this->getFileRecords()->first();
    }
}
