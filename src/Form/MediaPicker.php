<?php

namespace Slimani\MediaManager\Form;

use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Schemas\Components\Livewire;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Slimani\MediaManager\Livewire\MediaBrowser;
use Slimani\MediaManager\Models\File;
use Slimani\MediaManager\Models\Folder;

class MediaPicker extends FileUpload
{
    protected string $pickerId;

    protected string|\Closure|null $collection = null;

    protected string|\Closure|null $relationship = null;

    protected string|\Closure|null $directory = null;

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

    public function getPickerId(): string
    {
        return $this->pickerId ?? $this->getStatePath();
    }

    public function relationship(string|\Closure|null $name = null): static
    {
        $this->relationship = $name ?? $this->getName();

        return $this;
    }

    public function collection(string|\Closure|null $name): static
    {
        $this->collection = $name;

        return $this;
    }

    public function getCollection(): ?string
    {
        return $this->evaluate($this->collection);
    }

    public function directory(string|\Closure|null $directory): static
    {
        $this->directory = $directory;

        return $this;
    }

    public function getDirectory(): ?string
    {
        return $this->evaluate($this->directory);
    }

    public function getRelationship(): ?Relation
    {
        $name = $this->evaluate($this->relationship) ?: $this->getName();

        if (! $name) {
            return null;
        }

        $record = $this->getRecord();

        if (! $record) {
            return null;
        }

        if (! method_exists($record, $name)) {
            return null;
        }

        $relationship = $record->{$name}();

        if (! $relationship instanceof Relation) {
            return null;
        }

        return $relationship;
    }

    protected function getIdentifiersFromState($state): array
    {
        return array_map('strval', array_filter(Arr::wrap($state)));
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Standard FileUpload is used as a base for UI, but we disable its file handling
        // Standard FileUpload is used as a base for UI
        $this->saveRelationshipsUsing(null);
        $this->fetchFileInformation(false);

        // Generate a stable picker ID for this component instance
        $this->pickerId = str(static::class)->afterLast('\\')->append('-')->append($this->getName())->toString();

        $this->hintAction(
            Action::make('browse_media')
                ->label(__('media-manager::media-manager.actions.browse_media'))
                ->icon(Heroicon::FolderOpen)
                ->color('primary')
                ->schema(function (MediaPicker $component, Action $action): array {
                    $pickerId = $component->getPickerId();
                    $actionIndex = $action->getNestingIndex() ?? array_key_last($action->getLivewire()->mountedActions);
                    $statePath = "mountedActions.{$actionIndex}.data.selected_ids";
                    $folderStatePath = "mountedActions.{$actionIndex}.data.current_folder_id";

                    $actionData = $action->getLivewire()->mountedActions[$actionIndex]['data'] ?? [];
                    $selectedIds = $actionData['selected_ids'] ?? null;
                    $currentFolderId = $actionData['current_folder_id'] ?? null;

                    $items = $selectedIds
                        ? array_map(fn ($id) => "file-{$id}", array_filter(explode(',', $selectedIds)))
                        : collect((array) ($component->getState() ?? []))
                            ->map(fn ($id) => str_starts_with($id, 'file-') ? $id : "file-{$id}")
                            ->toArray();

                    return [
                        Livewire::make(MediaBrowser::class, [
                            'isPicker' => true,
                            'multiple' => $component->isMultiple(),
                            'selectedItems' => $items,
                            'pickerId' => $pickerId,
                            'statePath' => $statePath,
                            'acceptedFileTypes' => $component->getAcceptedFileTypes(),
                            'onSelect' => null,
                            'currentFolderId' => (int) $currentFolderId ?: null,
                        ])->key("media-browser-{$pickerId}-{$actionIndex}"),

                        Hidden::make('selected_ids')
                            ->extraAttributes(fn ($component) => [
                                'x-on:sync-picker-ids.window' => "\$event.detail.statePath === '{$statePath}' ? \$wire.set('{$component->getStatePath()}', \$event.detail.ids, false) : null",
                            ]),

                        Hidden::make('current_folder_id')
                            ->extraAttributes(fn ($component) => [
                                'x-on:media-folder-changed.window' => "\$event.detail.statePath === '{$statePath}' ? \$wire.set('{$component->getStatePath()}', \$event.detail.folderId, false) : null",
                            ]),
                    ];
                })
                ->slideOver()
                ->modalWidth('6xl')
                ->action(function (MediaPicker $component, array $data) {
                    $identifiers = array_filter(explode(',', $data['selected_ids'] ?? ''));
                    $files = File::whereIn('id', $identifiers)->get();

                    $component->state($identifiers);
                })
        );

        // No-op hydration since we handle IDs directly

        $this->getUploadedFileUsing(static function (MediaPicker $component, string $file): ?array {
            $fileRecord = File::find($file);

            if (! $fileRecord) {
                return null;
            }

            $media = $fileRecord->getFirstMedia('default');

            $url = $fileRecord->getUrl($component->getConversion());

            return [
                'name' => $media?->name ?? $media?->file_name ?? $fileRecord->name,
                'size' => $media?->size ?? $fileRecord->size ?? 0,
                'type' => $media?->mime_type ?? $fileRecord->mime_type,
                'url' => $url,
            ];
        });

        $this->saveUploadedFileUsing(static function (MediaPicker $component, TemporaryUploadedFile $file): ?string {
            $folderId = null;
            $directory = $component->getDirectory();

            if ($directory) {
                $segments = explode('/', trim($directory, '/'));
                $parentId = null;

                foreach ($segments as $segment) {
                    $folder = Folder::firstOrCreate([
                        'name' => $segment,
                        'parent_id' => $parentId,
                    ]);
                    $parentId = $folder->id;
                }
                $folderId = $parentId;
            }

            $fileModel = File::create([
                'name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'uploaded_by_user_id' => auth()->id(),
                'folder_id' => $folderId,
            ]);

            $media = $fileModel->addMediaFromString($file->get())
                ->usingFileName($file->getClientOriginalName())
                ->toMediaCollection('default');

            $fileModel->update([
                'size' => $media->size,
                'mime_type' => $media->mime_type,
                'extension' => $media->extension,
                'width' => $media->getCustomProperty('width'),
                'height' => $media->getCustomProperty('height'),
            ]);

            return (string) $fileModel->id;
        });

        // Map IDs/Relationships from model to Identifiers for the picker
        $this->afterStateHydrated(static function (MediaPicker $component, $state): void {

            if (blank($state)) {
                $record = $component->getRecord();
                if ($record) {
                    $relationship = $component->getRelationship();
                    if ($relationship) {
                        if ($relationship instanceof BelongsTo) {
                            $state = $record->getAttribute($relationship->getForeignKeyName());
                        } elseif ($relationship instanceof BelongsToMany or $relationship instanceof MorphToMany) {
                            $state = $relationship->get();
                        }
                    } else {
                        $state = $record->getAttribute($component->getName());
                    }
                }
            }

            if ($state instanceof Collection) {
                $component->state($state->map(fn ($file) => (string) $file->id)->filter()->values()->toArray());

                return;
            }

            if ($state instanceof Model) {
                $component->state($component->isMultiple() ? [(string) $state->id] : (string) $state->id);

                return;
            }

            if (is_scalar($state) && $state !== '') {
                $component->state($component->isMultiple() ? [(string) $state] : (string) $state);

                return;
            }

            if (empty($state)) {
                $component->state($component->isMultiple() ? [] : null);
            }
        });

        // Map identifiers back to the database relationships
        $this->dehydrateStateUsing(static function (MediaPicker $component, $state) {
            $identifiers = $component->getIdentifiersFromState($state);

            if ($component->isMultiple()) {
                return $identifiers;
            }

            return $identifiers[0] ?? null;
        });

        // Manually handle relationship saving
        $this->saveRelationshipsUsing(static function (MediaPicker $component, $state): void {
            $relationship = $component->getRelationship();
            $identifiers = $component->getIdentifiersFromState($state);

            if ($relationship instanceof BelongsTo) {
                $record = $component->getRecord();
                $column = $relationship->getForeignKeyName();
                $id = $identifiers[0] ?? null;

                if ($record->{$column} != $id) {
                    $record->{$column} = $id;
                    $record->save();
                }

                return;
            }

            if ($relationship instanceof BelongsToMany ||
                $relationship instanceof MorphToMany) {

                $pivotData = [];
                $collection = $component->getCollection() ?: $component->getName();

                foreach ($identifiers as $id) {
                    $pivotData[$id] = ['collection' => $collection];
                }

                $relationship->sync($pivotData);

                return;
            }

            // Fallback for direct attributes (not relationships)
            if (! $relationship && ! $component->isMultiple()) {
                $record = $component->getRecord();
                if ($record) {
                    $record->{$component->getName()} = $identifiers[0] ?? null;
                    $record->save();
                }
            }
        });
    }

    public function getValidationRules(): array
    {
        return []; // Bypass strict FileUpload validation
    }
}
