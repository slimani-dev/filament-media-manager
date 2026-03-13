<?php

namespace Slimani\MediaManager\Livewire;

use CodeWithDennis\FilamentSelectTree\SelectTree;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Components\Entry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\EmptyState;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use Hugomyb\FilamentMediaAction\Actions\MediaAction;
use Illuminate\Contracts\View\View;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Number;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Slimani\MediaManager\Components\MediaItem;
use Slimani\MediaManager\Infolists\Components\RepeatableEntry as CustomRepeatableEntry;
use Slimani\MediaManager\Models\File;
use Slimani\MediaManager\Models\Folder;
use Slimani\MediaManager\Models\Tag;

/**
 * @property-read Collection $items
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Tag> $tags
 *
 *
 * no need to add HasSchemas because interface HasForms extends HasSchemas
 */
class MediaBrowser extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms, InteractsWithSchemas {
        InteractsWithForms::getCachedSchemas insteadof InteractsWithSchemas;
        InteractsWithSchemas::getCachedSchemas as getBaseCachedSchemas;
    }
    use WithFileUploads;
    use WithPagination;

    public ?string $serializedOnSelect = null;

    public function clearCachedSchemas(): void
    {
        $this->cachedSchemas = [];
    }

    public ?Folder $currentFolder = null;

    // UI State
    public string $search = '';

    public string $sortField = '';

    public string $sortDirection = 'asc';

    public ?int $currentFolderId = null;

    public array $breadcrumbs = [];

    // Tag State
    public bool $isEditingTags = false;

    public array $activeTags = [];

    public ?int $editingFolderId = null;

    public ?int $selectedFileId = null;

    // Picker State
    public bool $showDetails = true;

    public bool $isPicker = false;

    public bool $multiple = false;

    public ?string $pickerId = null;

    // Filters State
    public bool $showFilters = false;

    public bool $showSelectedOnly = false;

    public array $filterTags = [];

    public ?string $filterType = null;

    public ?string $filterSizeMin = null;

    public ?string $filterSizeMax = null;

    public array $selectedItems = [];

    public int|string $perPage = 10;

    public function getPageName(): string
    {
        return 'media_browser_page';
    }

    public function boot()
    {
        if (app()->runningUnitTests()) {
            \Livewire\store($this)->set('forceRender', true);
        }
    }

    public function queryString()
    {
        if ($this->isPicker) {
            return [];
        }

        return [
            'sortField' => ['as' => 'sort_by', 'history' => true],
            'sortDirection' => ['as' => 'sort_dir', 'history' => true],
            'currentFolderId' => ['as' => 'folder', 'history' => true],
            'perPage' => ['history' => true],
            'showSelectedOnly' => ['history' => true],
            'paginators.'.$this->getPageName() => ['as' => $this->getPageName(), 'history' => true],
        ];
    }

    public ?string $statePath = null;

    public ?Collection $files = null;

    public ?array $acceptedFileTypes = [];

    public function updatedSortField(): void
    {
        $this->clearCachedSchemas();
        $this->setPage(1, $this->getPageName());
    }

    public function updatedSortDirection(): void
    {
        $this->clearCachedSchemas();
        $this->setPage(1, $this->getPageName());
    }

    public function updatedSearch(): void
    {
        $this->clearCachedSchemas();
        $this->setPage(1, $this->getPageName());
    }

    public function updatedFilterTags(): void
    {
        $this->clearCachedSchemas();
        $this->setPage(1, $this->getPageName());
    }

    public function updatedFilterType(): void
    {
        $this->clearCachedSchemas();
        $this->setPage(1, $this->getPageName());
    }

    public function updatedFilterSizeMin(): void
    {
        $this->clearCachedSchemas();
        $this->setPage(1, $this->getPageName());
    }

    public function updatedFilterSizeMax(): void
    {
        $this->clearCachedSchemas();
        $this->setPage(1, $this->getPageName());
    }

    public function mount(
        bool $isPicker = false,
        bool $multiple = false,
        ?string $pickerId = null,
        array $selectedItems = [],
        ?string $onSelect = null,
        ?string $statePath = null,
        ?array $acceptedFileTypes = []
    ): void {
        $this->isPicker = $isPicker;
        $this->multiple = $multiple;
        $this->pickerId = $pickerId;
        $this->selectedItems = $selectedItems;
        $this->serializedOnSelect = $onSelect;
        $this->statePath = $statePath;
        $this->acceptedFileTypes = $acceptedFileTypes;

        if ($this->serializedOnSelect) {
            $this->executeOnSelect();
        }

        if ($this->currentFolderId) {
            $this->currentFolder = Folder::query()->with(['tags'])->withCount(['children', 'files'])->find($this->currentFolderId);
            $this->generateBreadcrumbs();
        }
    }

    #[On('open-media-browser')]
    public function openMediaBrowser(string $pickerId, bool $multiple = false, array $selectedItems = []): void
    {
        $this->isPicker = true;
        $this->multiple = $multiple;
        $this->selectedItems = collect($selectedItems)
            ->map(fn ($id) => (is_string($id) && (str_starts_with($id, 'file-') || str_starts_with($id, 'folder-'))) ? $id : "file-{$id}")
            ->toArray();
        $this->pickerId = $pickerId;

        if (count($this->selectedItems) === 1) {
            $this->locateItem($this->selectedItems[0]);
        } else {
            $this->resetPage($this->getPageName());
        }

        $this->clearCachedSchemas();
        $this->dispatch('open-modal', id: 'media-browser-modal');
    }

    public function toggleDetailsAction(): Action
    {
        return Action::make('toggleDetails')
            ->label('Details')
            ->icon('heroicon-o-information-circle')
            ->hiddenLabel()
            ->color(fn () => $this->showDetails ? 'primary' : 'gray')
            ->action(function () {
                $this->showDetails = ! $this->showDetails;
                $this->clearCachedSchemas();
            });
    }

    public function toggleFiltersAction(): Action
    {
        return Action::make('toggleFilters')
            ->label('Filters')
            ->hiddenLabel()
            ->icon('heroicon-o-funnel')
            ->color(fn () => $this->showFilters || $this->hasActiveFilters() ? 'primary' : 'gray')
            ->badge(fn () => $this->getActiveFiltersCount() ?: null)
            ->action(function () {
                $this->showFilters = ! $this->showFilters;
                $this->clearCachedSchemas();
            });
    }

    public function toggleSortDirectionAction(): Action
    {
        return Action::make('toggleSortDirection')
            ->label('Sort Direction')
            ->hiddenLabel()
            ->icon(fn () => $this->sortDirection === 'asc' ? 'heroicon-o-bars-arrow-up' : 'heroicon-o-bars-arrow-down')
            ->color('gray')
            ->action(function () {
                $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
                $this->clearCachedSchemas();
            });
    }

    public function hasActiveFilters(): bool
    {
        return $this->getActiveFiltersCount() > 0;
    }

    public function getActiveFiltersCount(): int
    {
        $count = 0;

        if (! empty($this->filterTags)) {
            $count++;
        }

        if ($this->filterType !== null && $this->filterType !== '') {
            $count++;
        }

        if ($this->filterSizeMin !== null && $this->filterSizeMin !== '') {
            $count++;
        }

        if ($this->filterSizeMax !== null && $this->filterSizeMax !== '') {
            $count++;
        }

        return $count;
    }

    public function schema(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->schema([
                Grid::make(['default' => 1, 'lg' => 4])
                    ->schema([
                        Flex::make(fn () => [
                            Flex::make([
                                $this->createFolderAction(),
                                $this->uploadAction(),
                            ])->extraAttributes([
                                'class' => 'gap-2',
                            ])->visible(fn () => count($this->selectedItems) == 0),

                            Flex::make([
                                $this->bulkMoveAction(),
                                $this->bulkDeleteAction(),
                                Action::make('clearSelection')
                                    ->label('Clear')
                                    ->icon(Heroicon::XMark)
                                    ->color('danger')
                                    ->outlined()
                                    ->action(fn () => $this->clearSelection()),
                            ])->extraAttributes([
                                'class' => 'gap-2',
                            ])->from('xs')
                                ->visible(fn () => count($this->selectedItems) > 0),

                            Flex::make([
                                TextInput::make('search')
                                    ->live()
                                    ->debounce()
                                    ->hiddenLabel()
                                    ->placeholder('Search files...')
                                    ->prefixIcon('heroicon-m-magnifying-glass')
                                    ->columnSpan(1),
                                Flex::make([
                                    Select::make('sortField')
                                        ->hiddenLabel()
                                        ->options([
                                            'name' => 'Name',
                                            'created_at' => 'Date',
                                            'size' => 'Size',
                                            'mime_type' => 'Type',
                                        ])
                                        ->live()
                                        ->placeholder('Sort')
                                        ->extraAttributes([
                                            'class' => 'md:w-32',
                                        ])->grow(),
                                    $this->toggleSortDirectionAction(),
                                    $this->toggleFiltersAction(),
                                    $this->toggleDetailsAction(),
                                ])->extraAttributes([
                                    'class' => 'gap-4',
                                ])->alignEnd()
                                    ->grow(false),
                            ])->extraAttributes([
                                'class' => 'md:gap-4',
                            ])->from('md')
                                ->alignEnd()
                                ->grow(false),
                        ])->columnSpanFull()
                            ->from('md'),

                        Section::make()
                            ->schema([
                                Grid::make(['default' => 1, 'md' => 4])->schema([
                                    Select::make('filterType')
                                        ->label('File Type')
                                        ->options([
                                            'image' => 'Images',
                                            'video' => 'Videos',
                                            'audio' => 'Audio',
                                            'document' => 'Documents',
                                            'archive' => 'Archives',
                                        ])
                                        ->placeholder('All Types')
                                        ->live()
                                        ->columnSpan(1),
                                    Select::make('filterTags')
                                        ->label('Tags')
                                        ->multiple()
                                        ->options(Tag::pluck('name', 'id'))
                                        ->live()
                                        ->searchable()
                                        ->columnSpan(1),
                                    TextInput::make('filterSizeMin')
                                        ->label('Min Size (MB)')
                                        ->numeric()
                                        ->live()
                                        ->debounce()
                                        ->columnSpan(1),
                                    TextInput::make('filterSizeMax')
                                        ->label('Max Size (MB)')
                                        ->numeric()
                                        ->live()
                                        ->debounce()
                                        ->columnSpan(1),
                                ]),
                                Flex::make([
                                    Action::make('closeFilter')
                                        ->label('Close')
                                        ->icon(Heroicon::XCircle)
                                        ->color('danger')
                                        ->action(function () {
                                            $this->showFilters = ! $this->showFilters;
                                            $this->clearCachedSchemas();
                                        }),
                                    Action::make('clearFilters')
                                        ->label('Clear Filters')
                                        ->color('danger')
                                        ->outlined()
                                        ->disabled(fn () => ! $this->hasActiveFilters())
                                        ->action(function () {
                                            $this->reset(['filterTags', 'filterType', 'filterSizeMin', 'filterSizeMax']);
                                            $this->clearCachedSchemas();
                                            $this->resetPage();
                                        }),
                                ]),
                            ])
                            ->visible(fn () => $this->showFilters)
                            ->columnSpanFull(),
                        \Slimani\MediaManager\Components\Section::make()
                            ->heading(view(/** @var view-string */ 'media-manager::components.breadcrumbs', ['breadcrumbs' => $this->breadcrumbs]))
                            ->columnSpan(fn () => ['lg' => $this->showDetails ? 3 : 4]) // Dynamic Column Span
                            ->extraAttributes([
                                'class' => 'fi-media-grid-container',
                            ])
                            ->schema([

                                CustomRepeatableEntry::make('items')
                                    ->hiddenLabel()
                                    ->state($this->getItemsProperty())
                                    ->contained(false)
                                    ->schema(fn (CustomRepeatableEntry $component) => [
                                        MediaItem::make($item = $component->getItem())
                                            ->isPicker($this->isPicker)
                                            ->isAccepted($this->isPicker && $item instanceof File ? $this->isAccepted($item) : true),
                                    ])
                                    ->extraAttributes([
                                        'class' => 'fi-media-grid',
                                    ])
                                    ->visible(fn () => $this->getItemsProperty()->isNotEmpty()),

                                EmptyState::make('No files found')
                                    ->description('Upload a file or create a folder to get started.')
                                    ->icon(Heroicon::Document)
                                    ->contained(false)
                                    ->footer([
                                        $this->createFolderAction(),
                                        $this->uploadAction(),
                                    ])
                                    ->visible(fn () => $this->getItemsProperty()->isEmpty()),

                                ViewEntry::make('pagination')
                                    ->view(/** @var view-string */ 'media-manager::filament.pages.media-manager.pagination')
                                    ->viewData(['paginator' => $this->getItemsProperty()])
                                    ->visible(fn () => $this->getItemsProperty()->total() > 0),
                            ])
                            ->contained(false),

                        \Slimani\MediaManager\Components\Section::make()
                            ->heading('Details')
                            ->extraAttributes([
                                'class' => 'flex h-full no-negative-header-margin',
                            ])
                            ->columnSpan(['lg' => 1])
                            ->visible(fn () => $this->showDetails)
                            ->schema([
                                // 1. SELECTION DETAILS (1 or more items)
                                Grid::make(1)
                                    ->visible(fn () => count($this->selectedItems) > 0)
                                    ->schema(function () {
                                        if (count($this->selectedItems) > 1) {
                                            $data = $this->getSelectedItemsDataProperty();
                                            $items = $data['items'] ?? [];

                                            return [
                                                TextEntry::make('selection_title')
                                                    ->hiddenLabel()
                                                    ->state(fn () => count($this->selectedItems).' items selected')
                                                    ->weight(FontWeight::Bold)
                                                    ->size(TextSize::Large),

                                                Grid::make(1)->schema(fn () => collect($items)->map(function ($item) {
                                                    $type = $item instanceof Folder ? 'folder' : 'file';
                                                    $itemKey = "{$type}-{$item->id}";

                                                    return TextEntry::make('item_'.$itemKey)
                                                        ->hiddenLabel()
                                                        ->badge()
                                                        ->state($item->name)
                                                        ->icon($item instanceof Folder ? 'heroicon-m-folder' : 'heroicon-m-document')
                                                        ->iconColor($item instanceof Folder ? 'amber' : 'gray')
                                                        ->action(
                                                            Action::make('locate_'.$itemKey)
                                                                ->iconButton()
                                                                ->icon('heroicon-m-magnifying-glass-circle')
                                                                ->tooltip('Locate in Browser')
                                                                ->action(fn () => $this->locateItem($itemKey))
                                                        );
                                                })->toArray()),

                                                TextEntry::make('selection_size')
                                                    ->label('Total Size')
                                                    ->state(fn () => Number::fileSize($this->getSelectedItemsDataProperty()['size'] ?? 0))
                                                    ->badge(),

                                                Flex::make([
                                                    TextEntry::make('selection_files')
                                                        ->label('Files')
                                                        ->state(fn () => collect($this->selectedItems)->filter(fn ($i) => str_starts_with($i, 'file-'))->count())
                                                        ->badge(),
                                                    TextEntry::make('selection_folders')
                                                        ->label('Folders')
                                                        ->state(fn () => $this->getSelectedItemsDataProperty()['folders_count'] ?? 0)
                                                        ->visible(fn () => ($this->getSelectedItemsDataProperty()['folders_count'] ?? 0) > 0)
                                                        ->badge(),
                                                ]),

                                            ];
                                        }

                                        $itemKey = $this->selectedItems[0];
                                        if (! str_contains($itemKey, '-')) {
                                            $itemKey = "file-{$itemKey}";
                                        }

                                        [$type, $id] = explode('-', $itemKey);

                                        if ($type === 'file') {
                                            $file = File::find($id);

                                            return $file ? $this->fileDetailsSchema($file) : [];
                                        }

                                        $folder = Folder::find($id);

                                        return $folder ? $this->folderDetailsSchema($folder) : [];
                                    }),

                                // 2. NO SELECTION FALLBACK (Current Folder or Root)
                                Grid::make(1)
                                    ->visible(fn () => empty($this->selectedItems))
                                    ->schema(function () {
                                        if ($this->currentFolder) {
                                            return $this->folderDetailsSchema($this->currentFolder);
                                        }

                                        return [
                                            TextEntry::make('root_info')
                                                ->hiddenLabel()
                                                ->state('Media Library')
                                                ->weight(FontWeight::Bold)
                                                ->size(TextSize::Large),

                                            TextEntry::make('root_desc')
                                                ->hiddenLabel()
                                                ->state('Select a file or folder to view details.')
                                                ->color('gray'),
                                        ];
                                    }),
                            ]),
                    ]),
            ]);
    }

    public function deleteFile(int $id): void
    {
        $file = File::find($id);
        if ($file) {
            $file->delete();
            $this->selectedFileId = null;
            $this->dispatch('media-deleted');
        }
    }

    public function selectFile(int $id): void
    {
        $file = File::find($id);

        if ($this->isPicker && $file && ! $this->isAccepted($file)) {
            return;
        }

        Log::info("Media Browser - Selecting File ID: {$id}");
        $this->selectedFileId = $id;

        if ($this->isPicker && ! $this->multiple) {
            $this->selectedItems = ["file-{$id}"];
            $this->executeOnSelect();
        } else {
            $this->toggleSelection("file-{$id}");
        }

        $this->clearCachedSchemas();

        $this->syncState();
    }

    public function toggleSelection($id): void
    {
        if (str_starts_with($id, 'file-')) {
            $fileId = str_replace('file-', '', $id);
            $file = File::find($fileId);

            if ($this->isPicker && $file && ! $this->isAccepted($file)) {
                return;
            }
        }

        if (collect($this->selectedItems)->contains($id)) {
            Log::info("Media Browser - Deselecting Item: {$id}");
            $this->selectedItems = collect($this->selectedItems)->reject(fn ($item) => $item === $id)->toArray();
        } else {
            Log::info("Media Browser - Selecting Item: {$id}");
            if ($this->isPicker && ! $this->multiple) {
                $this->selectedItems = [$id];
            } else {
                $this->selectedItems[] = $id;
            }
        }

        $this->isEditingTags = false;
        $this->editingFolderId = null;
        $this->clearCachedSchemas();

        if (str_starts_with($id, 'file-')) {
            $this->executeOnSelect();
        }

        $this->syncState();
    }

    public function addToSelection(string $id): void
    {
        if (! in_array($id, $this->selectedItems)) {
            if ($this->isPicker && ! $this->multiple) {
                $this->selectedItems = [$id];
            } else {
                $this->selectedItems[] = $id;
            }
            $this->isEditingTags = false;
            $this->editingFolderId = null;
            $this->clearCachedSchemas();

            if (str_starts_with($id, 'file-')) {
                $this->executeOnSelect();
            }

            $this->syncState();
        }
    }

    protected function executeOnSelect(): void
    {
        if (! $this->serializedOnSelect) {
            return;
        }

        try {
            /** @var \Closure $callback */
            $callback = unserialize($this->serializedOnSelect)->getClosure();

            $fileIds = collect($this->selectedItems)
                ->filter(fn ($i) => str_starts_with($i, 'file-'))
                ->map(fn ($i) => str_replace('file-', '', $i))
                ->toArray();

            $files = File::whereIn('id', $fileIds)->get();

            if ($files->isNotEmpty()) {
                $callback($files, $this);
            }
        } catch (\Throwable $e) {
            Log::error('Failed to execute onSelect closure: '.$e->getMessage());
        }
    }

    protected function syncState(): void
    {
        if ($this->statePath) {
            $ids = collect($this->selectedItems)
                ->filter(fn ($i) => str_starts_with($i, 'file-'))
                ->map(fn ($i) => str_replace('file-', '', $i))
                ->toArray();

            $this->dispatch('sync-picker-ids',
                statePath: $this->statePath,
                ids: implode(',', $ids),
            );
        }
    }

    public function isAccepted(File $file): bool
    {
        if (empty($this->acceptedFileTypes)) {
            return true;
        }

        foreach ($this->acceptedFileTypes as $type) {
            $typePattern = str_replace(['/', '*'], ['\/', '.*'], $type);
            if (preg_match("/^{$typePattern}$/i", $file->mime_type)) {
                return true;
            }
        }

        return false;
    }

    public function clearSelection(): void
    {
        $this->selectedItems = [];
        $this->clearCachedSchemas();
    }

    /**
     * @param  ImageEntry  $component
     */
    public function getRepeaterItemKey(Entry $component, string $prefix, string $suffix): int
    {
        return str($component->getStatePath())
            ->replaceFirst($prefix, '')
            ->replaceEnd($suffix, '')
            ->toInteger();
    }

    protected function applySearchAndFiltersAndDeepSearchToQuery($query, $isFolder = false)
    {
        $column = $isFolder ? 'parent_id' : 'folder_id';

        if ($this->search || $this->hasActiveFilters()) {
            // 1. Recursive Tree Scope
            if ($this->currentFolderId !== null) {
                // Fetch current folder + all recursive descendants
                $folderIds = array_merge(
                    [$this->currentFolderId],
                    $this->currentFolder?->getAllDescendantIds() ?? []
                );
                $query->whereIn($column, $folderIds);
            }
            // If currentFolderId is null, we are at Root.
            // By default, every folder is either at Root (parent_id null) or is a descendant.
            // So we don't need a strict whereIn at root as it would be the entire table.

            // 2. Search Keyword
            if ($this->search) {
                $query->where(function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                        ->orWhereHas('tags', fn ($t) => $t->where('name', 'like', "%{$this->search}%"));
                });
            }

            // 3. Filter Tags
            if (! empty($this->filterTags)) {
                $query->whereHas('tags', function ($q) {
                    $q->whereIn('media_tags.id', $this->filterTags);
                });
            }
        } else {
            // Not searching/filtering: only show direct children of the current folder
            $query->where($column, $this->currentFolderId);
        }

        return $query;
    }

    public function getFoldersProperty()
    {
        // Don't fetch folders if user specifically filters by file types or sizes
        if (($this->filterType || ($this->filterSizeMin !== null && $this->filterSizeMin !== '') || ($this->filterSizeMax !== null && $this->filterSizeMax !== '')) && ($this->search || $this->hasActiveFilters())) {
            return collect();
        }

        $query = Folder::query()
            ->with(['tags'])
            ->withCount(['children', 'files']);

        $query = $this->applySearchAndFiltersAndDeepSearchToQuery($query, true);

        return $query->get();
    }

    public function getMediaFilesProperty()
    {
        $query = File::query()->with(['tags']);

        $query = $this->applySearchAndFiltersAndDeepSearchToQuery($query, false);

        // Apply file-specific criteria
        if ($this->search || $this->hasActiveFilters()) {
            if ($this->filterType) {
                if ($this->filterType === 'document') {
                    $query->whereIn('mime_type', ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain']);
                } elseif ($this->filterType === 'archive') {
                    $query->whereIn('mime_type', ['application/zip', 'application/x-rar-compressed', 'application/x-7z-compressed', 'application/x-tar']);
                } else {
                    $query->where('mime_type', 'like', "{$this->filterType}/%");
                }
            }

            if ($this->filterSizeMin !== null && $this->filterSizeMin !== '') {
                $query->where('size', '>=', (float) $this->filterSizeMin * 1024 * 1024); // MB to Bytes
            }

            if ($this->filterSizeMax !== null && $this->filterSizeMax !== '') {
                $query->where('size', '<=', (float) $this->filterSizeMax * 1024 * 1024); // MB to Bytes
            }
        }

        return $query->get();
    }

    public function getItemsProperty()
    {
        if ($this->showSelectedOnly) {
            $selectedFolderIds = [];
            $selectedFileIds = [];

            foreach ($this->selectedItems as $itemKey) {
                [$type, $id] = explode('-', $itemKey);
                if ($type === 'folder') {
                    $selectedFolderIds[] = $id;
                } else {
                    $selectedFileIds[] = $id;
                }
            }

            $folders = Folder::query()->whereIn('id', $selectedFolderIds)->with(['tags'])->withCount(['children', 'files'])->get();
            $files = File::query()->whereIn('id', $selectedFileIds)->with(['tags'])->get();
            $allItems = $folders->concat($files);
        } else {
            $folders = $this->getFoldersProperty();
            $files = $this->getMediaFilesProperty();
            $allItems = $folders->concat($files);
        }

        $sortProperty = $this->sortField ?: 'name';
        if ($sortProperty === 'mime_type') {
            $allItems = $allItems->map(function ($item) {
                $item->sort_type = $item instanceof Folder ? 'folder' : $item->mime_type;

                return $item;
            });
            $sortProperty = 'sort_type';
        }

        $sortFlags = $sortProperty === 'name' ? (SORT_NATURAL | SORT_FLAG_CASE) : SORT_REGULAR;

        $sortCallback = function ($item) use ($sortProperty) {
            if ($sortProperty === 'created_at') {
                return $item->created_at?->timestamp ?? 0;
            }

            return $item->{$sortProperty} ?? '';
        };

        $allItems = $allItems->sort(function ($a, $b) use ($sortProperty, $sortCallback) {
            // Folders always first (priority 0) then files (priority 1)
            $aPriority = $a instanceof Folder ? 0 : 1;
            $bPriority = $b instanceof Folder ? 0 : 1;

            if ($aPriority !== $bPriority) {
                return $aPriority <=> $bPriority;
            }

            // Same category? Apply standard sort
            $valA = $sortCallback($a);
            $valB = $sortCallback($b);

            $result = ($sortProperty === 'name')
                ? strnatcasecmp((string) $valA, (string) $valB)
                : $valA <=> $valB;

            return $this->sortDirection === 'desc' ? -$result : $result;
        });

        $allItems = $allItems->values();

        // Manual pagination logic
        $page = $this->getPage($this->getPageName());
        $perPage = $this->perPage;

        if ($perPage === 'all') {
            return new LengthAwarePaginator(
                $allItems,
                $allItems->count(),
                $allItems->count() ?: 1,
                1,
                [
                    'path' => Paginator::resolveCurrentPath(),
                    'pageName' => $this->getPageName(),
                ]
            );
        }

        $items = $allItems->forPage($page, $perPage);

        return new LengthAwarePaginator(
            $items,
            $allItems->count(),
            $perPage,
            $page,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => $this->getPageName(),
            ]
        );
    }

    public function getSelectedFileProperty(): File|array|null
    {
        if (count($this->selectedItems) !== 1) {
            return null;
        }

        [$type, $id] = explode('-', $this->selectedItems[0]);

        if ($type !== 'file') {
            return null;
        }

        return File::find($id);
    }

    public function getSelectedItemsDataProperty(): array
    {
        if (empty($this->selectedItems)) {
            return [];
        }

        $filesCount = 0;
        $foldersCount = 0;
        $totalSize = 0;
        $items = [];

        foreach ($this->selectedItems as $itemKey) {
            [$type, $id] = explode('-', $itemKey);

            if ($type === 'folder') {
                $folder = Folder::find($id);

                if ($folder) {
                    $items[] = $folder;
                    $descendantIds = $folder->getAllDescendantIds();
                    $allFolderIdsInThisSelection = array_merge([$folder->id], $descendantIds);

                    // Add all folders found in this branch to the total count
                    $foldersCount += count($allFolderIdsInThisSelection);

                    // Add all files found in this branch
                    $filesCount += File::whereIn('folder_id', $allFolderIdsInThisSelection)->count();
                    $totalSize += File::whereIn('folder_id', $allFolderIdsInThisSelection)->sum('size');
                }
            } else {
                // It's a single file selection
                $file = File::find($id);
                if ($file) {
                    $items[] = $file;
                    $filesCount++;
                    $totalSize += $file->size;
                }
            }
        }

        return [
            'files_count' => $filesCount,
            'folders_count' => $foldersCount,
            'size' => $totalSize,
            'items' => $items,
        ];
    }

    protected function fileDetailsSchema(File $file): array
    {
        return [
            ImageEntry::make('sel_preview')
                ->hiddenLabel()
                ->state($file->getUrl('preview'))
                ->imageWidth('100%')
                ->imageHeight('auto')
                ->extraImgAttributes(['class' => 'object-contain w-full'])
                ->visible(collect(['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'])->contains($file->mime_type)),

            TextEntry::make('sel_thumb')
                ->hiddenLabel()
                ->state(new HtmlString(Blade::render('<div class="flex items-center justify-center bg-gray-100 dark:bg-gray-800 rounded-lg h-32"><x-heroicon-o-document-text class="w-12 h-12 text-gray-400" /></div>')))
                ->html()
                ->visible(! collect(['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'])->contains($file->mime_type)),

            TextEntry::make('sel_name')
                ->hiddenLabel()
                ->state($file->name.($file->extension ? '.'.$file->extension : ''))
                ->weight(FontWeight::Bold),

            Flex::make([
                TextEntry::make('sel_size')
                    ->label('Size')
                    ->state(Number::fileSize($file->size ?? 0))
                    ->badge(),
                TextEntry::make('sel_type')
                    ->label('Type')
                    ->state($file->mime_type)
                    ->badge(),
            ]),

            TextEntry::make('sel_caption')
                ->state($file->caption)
                ->visible((bool) $file->caption),

            TextEntry::make('sel_path')
                ->label('Public URL')
                ->state($file->getUrl())
                ->copyable()
                ->limit(30)
                ->hintActions([
                    Action::make('locate')
                        ->iconButton()
                        ->icon(Heroicon::OutlinedMagnifyingGlassCircle)
                        ->action(fn () => $this->locateItem("file-{$file->id}")),
                    MediaAction::make($file->name)
                        ->iconButton()
                        ->slideOver()
                        ->icon(Heroicon::OutlinedEye)
                        ->media($file->getUrl()),
                    Action::make('open_url')
                        ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                        ->iconButton()
                        ->url($file->getUrl(), true),
                ]),

            TextEntry::make('sel_created_at')
                ->label('Uploaded')
                ->state($file->created_at)
                ->since()
                ->color('gray'),

            TagsInput::make('activeTags')
                ->label('Tags')
                ->suggestions(Tag::pluck('name')->toArray())
                ->live()
                ->visible(fn () => $this->isEditingTags)
                ->hintAction(
                    Action::make('saveTags')
                        ->icon('heroicon-m-check')
                        ->color('success')
                        ->action(fn () => $this->saveTags())
                ),

            TextEntry::make('tags_display')
                ->label('Tags')
                ->state($file->tags->pluck('name') ?: 'No tags')
                ->visible(fn () => ! $this->isEditingTags)
                ->badge()
                ->hintAction(
                    Action::make('editTags')
                        ->icon('heroicon-m-pencil-square')
                        ->action(function () use ($file) {
                            $this->selectedFileId = $file->id;
                            $this->editingFolderId = null;
                            $this->activeTags = $file->tags->pluck('name')->toArray();
                            $this->isEditingTags = true;
                            $this->clearCachedSchemas();
                        })
                ),

        ];
    }

    protected function folderDetailsSchema(Folder $folder): array
    {
        $recursiveStats = $folder->getRecursiveStats();

        return [
            TextEntry::make('sel_folder_name')
                ->label('Folder')
                ->state($folder->name)
                ->weight(FontWeight::Bold)
                ->size(TextSize::Large),

            TextEntry::make('sel_folder_created_at')
                ->label('Created')
                ->state($folder->created_at)
                ->date(),

            TextEntry::make('sel_folder_total_size')
                ->label('Total Size')
                ->state(Number::fileSize($recursiveStats['total_size']))
                ->badge()
                ->color('success'),

            Flex::make([
                /*TextEntry::make('sel_folder_items')
                    ->label('Children')
                    ->state(($folder->children_count ?? 0) + ($folder->files_count ?? 0))
                    ->suffix(' items'),*/

                /* TextEntry::make('sel_folder_recursive_items')
                     ->label('Total Items')
                     ->state($recursiveStats['files_count'] + $recursiveStats['folders_count'])
                     ->suffix(' items')
                     ->badge(),*/

                // Separated Files Count
                TextEntry::make('sel_folder_recursive_files')
                    ->label('Files')
                    ->state($recursiveStats['files_count'])
                    ->badge(),

                // Separated Folders Count (Nested sub-folders)
                TextEntry::make('sel_folder_recursive_folders')
                    ->label('Folders')
                    ->state($recursiveStats['folders_count'])
                    ->badge(),
            ]),

            TagsInput::make('activeTags')
                ->label('Tags')
                ->suggestions(Tag::pluck('name')->toArray())
                ->live()
                ->visible(fn () => $this->isEditingTags)
                ->hintAction(
                    Action::make('saveFolderTags')
                        ->icon('heroicon-m-check')
                        ->color('success')
                        ->action(fn () => $this->saveTags())
                ),

            TextEntry::make('folder_tags_display')
                ->label('Tags')
                ->state($folder->tags->pluck('name') ?: 'No tags')
                ->visible(fn () => ! $this->isEditingTags)
                ->badge()
                ->hintAction(
                    Action::make('editFolderTags')
                        ->icon('heroicon-m-pencil-square')
                        ->action(function () use ($folder) {
                            $this->editingFolderId = $folder->id;
                            $this->selectedFileId = null;
                            $this->activeTags = $folder->tags->pluck('name')->toArray();
                            $this->isEditingTags = true;
                            $this->clearCachedSchemas();
                        })
                ),

            Action::make('locate')
                ->label('Locate in Browser')
                ->icon('heroicon-o-magnifying-glass-circle')
                ->color('primary')
                ->link()
                ->size('sm')
                ->action(fn () => $this->locateItem("folder-{$folder->id}")),
        ];
    }

    public function locateItem(string $itemKey): void
    {
        $this->search = '';

        if (! str_contains($itemKey, '-')) {
            $itemKey = "file-{$itemKey}";
        }

        [$type, $id] = explode('-', $itemKey);

        $parentId = null;
        if ($type === 'folder') {
            $folder = Folder::find($id);
            if ($folder) {
                $parentId = $folder->parent_id;
            }
        } else {
            $file = File::find($id);
            if ($file) {
                $parentId = $file->folder_id;
            }
        }

        // 1. Move to the correct folder
        $this->setCurrentFolder($parentId);

        // 2. Clear "Show Selected Only" to see the context
        $this->showSelectedOnly = false;

        // 3. Calculate page
        $perPage = (int) $this->perPage;
        if ($perPage > 0) {
            $folders = $this->getFoldersProperty();
            $files = $this->getMediaFilesProperty();
            $allItems = $folders->concat($files);

            // Re-apply sorting to match getItemsProperty
            $sortProperty = $this->sortField ?: 'name';
            if ($sortProperty === 'mime_type') {
                $allItems = $allItems->map(function ($item) {
                    $item->sort_type = $item instanceof Folder ? 'folder' : $item->mime_type;

                    return $item;
                });
                $sortProperty = 'sort_type';
            }

            $sortCallback = function ($item) use ($sortProperty) {
                if ($sortProperty === 'created_at') {
                    return $item->created_at?->timestamp ?? 0;
                }

                return $item->{$sortProperty} ?? '';
            };

            $allItems = $allItems->sort(function ($a, $b) use ($sortProperty, $sortCallback) {
                $aPriority = $a instanceof Folder ? 0 : 1;
                $bPriority = $b instanceof Folder ? 0 : 1;

                if ($aPriority !== $bPriority) {
                    return $aPriority <=> $bPriority;
                }

                $valA = $sortCallback($a);
                $valB = $sortCallback($b);

                $result = ($sortProperty === 'name')
                    ? strnatcasecmp((string) $valA, (string) $valB)
                    : $valA <=> $valB;

                return $this->sortDirection === 'desc' ? -$result : $result;
            })->values();

            $index = $allItems->search(function ($item) use ($type, $id) {
                return ($item instanceof Folder ? 'folder' : 'file') === $type && $item->id == $id;
            });

            if ($index !== false) {
                $page = floor($index / $perPage) + 1;
                $this->setPage($page, $this->getPageName());
            }
        }

        $this->clearCachedSchemas();
    }

    public function setCurrentFolder(?int $id): void
    {
        Log::info("Media Browser - Setting Current Folder to ID: {$id}");
        $this->currentFolderId = $id;
        $this->currentFolder = $id ? Folder::query()->with(['tags'])->withCount(['children', 'files'])->find($id) : null;
        $this->editingFolderId = null;
        $this->isEditingTags = false;
        $this->generateBreadcrumbs();
        $this->setPage(1, $this->getPageName());
    }

    public function saveTags(): void
    {
        $model = null;

        if (count($this->selectedItems) === 1) {
            [$type, $id] = explode('-', $this->selectedItems[0]);
            if ($type === 'file') {
                $model = File::find($id);
            } else {
                $model = Folder::find($id);
            }
        } elseif ($this->editingFolderId) {
            $model = Folder::find($this->editingFolderId);
        } elseif ($this->currentFolderId) {
            $model = $this->currentFolder;
        }

        if ($model) {
            $tagIds = collect($this->activeTags)->map(function ($name) {
                return Tag::firstOrCreate(['name' => $name])->id;
            })->toArray();

            $model->tags()->sync($tagIds);
        }

        $this->isEditingTags = false;
        $this->editingFolderId = null;
        $this->clearCachedSchemas();
    }

    public function generateBreadcrumbs(): void
    {
        $breadcrumbs = [];
        $folder = $this->currentFolder;

        while ($folder) {
            array_unshift($breadcrumbs, [
                'id' => $folder->id,
                'name' => $folder->name,
            ]);
            $folder = $folder->parent;
        }

        $this->breadcrumbs = $breadcrumbs;
    }

    public function bulkDeleteAction(): Action
    {
        return Action::make('bulkDelete')
            ->label('Delete')
            ->icon(Heroicon::Trash)
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Delete selected items?')
            ->modalDescription('Are you sure you want to delete the selected items? This action cannot be undone.')
            ->modalSubmitActionLabel('Yes, delete them')
            ->action(fn () => $this->deleteSelectedItems());
    }

    public function bulkMoveAction(): Action
    {
        return Action::make('bulkMove')
            ->label('Move')
            ->icon(Heroicon::FolderArrowDown)
            ->color('gray')
            ->schema([
                SelectTree::make('folder_id')
                    ->label('Target Folder')
                    ->query(Folder::query()->orderBy('name'), 'name', 'parent_id')
                    ->prepend([
                        'name' => 'Root',
                        'value' => 0,
                    ])
                    ->enableBranchNode()
                    ->withCount()
                    ->required()
                    ->searchable(),
            ])
            ->action(fn (array $data) => $this->moveSelectedItems($data['folder_id']));
    }

    protected function getFolderTreePath(Folder $folder): string
    {
        $path = [$folder->name];
        $parent = $folder->parent;

        while ($parent) {
            array_unshift($path, $parent->name);
            $parent = $parent->parent;
        }

        return implode(' / ', $path);
    }

    public function createFolderAction(): Action
    {
        return Action::make('createFolder')
            ->label('Create Folder')
            ->icon(Heroicon::OutlinedFolderPlus)
            ->schema([
                TextInput::make('name')
                    ->label('Folder Name')
                    ->required(),
            ])
            ->action(function (array $data) {
                Folder::create([
                    'name' => $data['name'],
                    'parent_id' => $this->currentFolderId,
                ]);

                $this->dispatch('media-uploaded');
                $this->clearCachedSchemas();
            });
    }

    public function goUpAction(): Action
    {
        return Action::make('goUp')
            ->label('Up')
            ->icon('heroicon-m-arrow-left')
            ->iconButton()
            ->color('gray')
            ->action(function () {
                if ($this->currentFolder) {
                    $this->setCurrentFolder($this->currentFolder->parent_id);
                }

                $this->dispatch('media-uploaded');
                $this->clearCachedSchemas();
            })->visible(fn () => $this->currentFolderId !== null);
    }


    public function uploadAction(): Action
    {
        return Action::make('upload')
            ->label('Upload')
            ->icon('heroicon-m-arrow-up-tray')
            ->schema([
                FileUpload::make('files')
                    ->label('Files')
                    ->multiple()
                    ->disk(fn () => filament('media-manager')->getDisk())
                    ->required(),
                TagsInput::make('tags')
                    ->suggestions(Tag::pluck('name')->toArray()),
                TextInput::make('caption'),
                TextInput::make('alt_text'),
            ])
            ->action(function (array $data) {
                foreach ($data['files'] as $file) {

                    $filename = $file instanceof UploadedFile
                        ? $file->getClientOriginalName()
                        : basename($file);

                    $name = pathinfo($filename, PATHINFO_FILENAME);

                    $fileModel = File::create([
                        'name' => $name,
                        'uploaded_by_user_id' => auth()->id(),
                        'folder_id' => $this->currentFolderId,
                        'caption' => $data['caption'] ?? null,
                        'alt_text' => $data['alt_text'] ?? null,
                    ]);

                    if (isset($data['tags'])) {
                        $tagIds = collect($data['tags'])->map(function ($name) {
                            return Tag::firstOrCreate(['name' => $name])->id;
                        })->toArray();

                        $fileModel->tags()->sync($tagIds);
                    }

                    try {
                        $diskName = filament('media-manager')->getDisk();

                        if ($file instanceof UploadedFile) {
                            $media = $fileModel->addMediaFromString($file->get())
                                ->usingFileName($filename)
                                ->toMediaCollection('default', $diskName);
                        } else {
                            // $file is a path representing a temporarily uploaded file string from Livewire FileUpload
                            $disk = Storage::disk($diskName);

                            // Try to find the actual path by checking existence
                            // Some S3 providers return 403 for HeadObject if file doesn't exist or permissions are tight
                            $pathsToTry = [
                                $file,
                                'livewire-tmp/'.$file,
                            ];

                            $actualPath = $file;
                            foreach ($pathsToTry as $candidate) {
                                try {
                                    if ($disk->exists($candidate)) {
                                        $actualPath = $candidate;
                                        break;
                                    }
                                } catch (\Throwable $e) {
                                    // If exists() fails, we'll try the next candidate or let addMediaFromDisk fail
                                    continue;
                                }
                            }

                            try {
                                $media = $fileModel->addMediaFromDisk($actualPath, $diskName)
                                    ->usingFileName($filename)
                                    ->toMediaCollection('default', $diskName);
                            } catch (\Throwable $e) {
                                // Fallback: If addMediaFromDisk fails, try getting the content directly
                                // (sometimes GetObject works when HeadObject/exists fails)
                                try {
                                    $content = $disk->get($actualPath);
                                    $media = $fileModel->addMediaFromString($content)
                                        ->usingFileName($filename)
                                        ->toMediaCollection('default', $diskName);
                                } catch (\Throwable $finalError) {
                                    // Re-throw the original error if fallback also fails
                                    throw $e;
                                }
                            }
                        }

                        $fileModel->update([
                            'name' => $media->file_name,
                            'size' => $media->size,
                            'mime_type' => $media->mime_type,
                            'extension' => $media->extension,
                            'width' => $media->getCustomProperty('width'),
                            'height' => $media->getCustomProperty('height'),
                        ]);
                    } catch (\Throwable $e) {
                        // Log error or handle gracefully
                        Log::error('Media Manager Upload Error: '.$e->getMessage());

                        // We still have the record but media was not attached.
                        // We might want to delete the record if media fails
                        $fileModel->delete();

                        $this->dispatch('media-upload-error', message: $e->getMessage());
                    }
                }

                $this->dispatch('media-uploaded');
                $this->clearCachedSchemas();
            });
    }

    public function selectSelectedItems(): void
    {
        if (! $this->isPicker) {
            return;
        }

        $fileIds = collect($this->selectedItems)
            ->filter(fn ($id) => str_starts_with($id, 'file-'))
            ->map(fn ($id) => str_replace('file-', '', $id))
            ->toArray();

        // If no items are selected in multiple, check if a single file is being viewed
        if (empty($fileIds) && $this->selectedFileId) {
            $fileIds = [$this->selectedFileId];
        }

        if (empty($fileIds)) {
            Notification::make()
                ->title('Please select at least one file')
                ->warning()
                ->send();

            return;
        }

        $uuids = File::with('media')->whereIn('id', $fileIds)->get()->map(fn ($file) => $file->getFirstMedia('default')?->uuid)->filter()->values()->toArray();

        $this->dispatch('media-picker-selected', [
            'pickerId' => $this->pickerId,
            'uuids' => $uuids,
        ]);

        $this->dispatch('close-modal', id: 'media-browser-modal');
    }

    public function deleteSelectedItems(): void
    {
        foreach ($this->selectedItems as $itemKey) {
            if (! str_contains($itemKey, '-')) {
                $itemKey = "file-{$itemKey}";
            }

            [$type, $id] = explode('-', $itemKey);

            if ($type === 'folder') {
                $folder = Folder::find($id);
                if ($folder) {
                    // Recursive deletion of children and files
                    $this->recursiveDeleteFolder($folder);
                }
            } else {
                $file = File::find($id);
                if ($file) {
                    $file->delete();
                }
            }
        }

        $this->selectedItems = [];
        $this->clearCachedSchemas();
        $this->dispatch('media-updated');

        Notification::make()
            ->title('Items deleted successfully')
            ->success()
            ->send();
    }

    protected function recursiveDeleteFolder(Folder $folder): void
    {
        // Delete all files in this folder
        foreach ($folder->files as $file) {
            $file->delete();
        }

        // Recursively delete sub-folders
        foreach ($folder->children as $subFolder) {
            $this->recursiveDeleteFolder($subFolder);
        }

        // Finally delete the folder itself
        $folder->delete();
    }

    public function moveSelectedItems(?int $targetFolderId): void
    {
        // Treat 0 as null (Root)
        $targetFolderId = ($targetFolderId === 0 || $targetFolderId === null) ? null : $targetFolderId;

        foreach ($this->selectedItems as $itemKey) {
            if (! str_contains($itemKey, '-')) {
                $itemKey = "file-{$itemKey}";
            }

            [$type, $id] = explode('-', $itemKey);

            if ($type === 'folder') {
                $folder = Folder::find($id);
                // Prevent moving a folder into itself or its descendants
                if ($folder && $targetFolderId != $folder->id) {
                    $descendantIds = $folder->getAllDescendantIds();
                    if (! in_array($targetFolderId, $descendantIds)) {
                        $folder->update(['parent_id' => $targetFolderId]);
                    }
                }
            } else {
                $file = File::find($id);
                if ($file) {
                    $file->update(['folder_id' => $targetFolderId]);
                }
            }
        }

        $this->selectedItems = [];
        $this->clearCachedSchemas();
        $this->dispatch('media-updated');

        Notification::make()
            ->title('Items moved successfully')
            ->success()
            ->send();
    }

    public function render(): View
    {
        return view('media-manager::livewire.media-browser');
    }
}
