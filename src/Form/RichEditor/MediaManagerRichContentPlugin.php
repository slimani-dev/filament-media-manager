<?php

namespace Slimani\MediaManager\Form\RichEditor;

use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\EditorCommand;
use Filament\Forms\Components\RichEditor\FileAttachmentProviders\Contracts\FileAttachmentProvider;
use Filament\Forms\Components\RichEditor\Plugins\Contracts\HasFileAttachmentProvider;
use Filament\Forms\Components\RichEditor\Plugins\Contracts\HasToolbarButtons;
use Filament\Forms\Components\RichEditor\Plugins\Contracts\RichContentPlugin;
use Filament\Forms\Components\RichEditor\RichEditorTool;
use Filament\Schemas\Components\Livewire;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Slimani\MediaManager\Form\RichEditor\FileAttachmentProviders\MediaManagerFileAttachmentProvider;
use Slimani\MediaManager\Form\RichEditor\Nodes\MediaFileNode;
use Slimani\MediaManager\Livewire\MediaBrowser;
use Slimani\MediaManager\Models\File;

class MediaManagerRichContentPlugin implements HasToolbarButtons, RichContentPlugin, HasFileAttachmentProvider
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getFileAttachmentProvider(): ?FileAttachmentProvider
    {
        return MediaManagerFileAttachmentProvider::make();
    }

    public function getTipTapPhpExtensions(): array
    {
        return [
            app(MediaFileNode::class),
        ];
    }

    public function getTipTapJsExtensions(): array
    {
        return [];
    }

    public function getEditorTools(): array
    {
        return [
            RichEditorTool::make('mediaLibrary')
                ->label('Media Library')
                ->icon(Heroicon::Photo)
                ->action(),
        ];
    }

    public function getEditorActions(): array
    {
        return [
            Action::make('mediaLibrary')
                ->label('Media Library')
                ->modalWidth(Width::SixExtraLarge)
                ->modalSubmitActionLabel('Insert')
                ->schema(function (RichEditor $component, Action $action): array {
                    $pickerId = $component->getStatePath();
                    // Identify the correct state path inside the action modal
                    $actionIndex = $action->getNestingIndex() ?? array_key_last($action->getLivewire()->mountedActions);
                    $statePath = "mountedActions.{$actionIndex}.data.selected_ids";
                    $folderStatePath = "mountedActions.{$actionIndex}.data.current_folder_id";

                    $actionData = $action->getLivewire()->mountedActions[$actionIndex]['data'] ?? [];
                    $selectedIds = $actionData['selected_ids'] ?? '';
                    $currentFolderId = $actionData['current_folder_id'] ?? null;

                    $items = array_map(fn ($id) => "file-{$id}", array_filter(explode(',', $selectedIds)));

                    return [
                        Livewire::make(MediaBrowser::class, [
                            'pickerId' => $pickerId,
                            'statePath' => $statePath,
                            'multiple' => true,
                            'selectedItems' => $items,
                            'currentFolderId' => $currentFolderId ? (int) $currentFolderId : null,
                        ])->key("media-browser-{$pickerId}-{$actionIndex}"),
                        Hidden::make('selected_ids')
                            ->extraAttributes([
                                'x-on:sync-picker-ids.window' => "
                                    if (\$event.detail.statePath === '{$statePath}') {
                                        \$wire.set('{$statePath}', \$event.detail.ids)
                                    }
                                ",
                            ]),
                        Hidden::make('current_folder_id')
                            ->extraAttributes([
                                'x-on:media-folder-changed.window' => "
                                    if (\$event.detail.statePath === '{$statePath}') {
                                        \$wire.set('{$folderStatePath}', \$event.detail.folderId)
                                    }
                                ",
                            ]),
                    ];
                })
                ->action(function (array $data, RichEditor $component, array $arguments): void {
                    $ids = array_filter(explode(',', $data['selected_ids'] ?? ''));

                    if (empty($ids)) {
                        return;
                    }

                    $files = File::findMany($ids);
                    $commands = [];

                    foreach ($files as $file) {
                        $isImage = str($file->mime_type)->startsWith('image/');

                        if ($isImage) {
                            $url = $component->getFileAttachmentUrl($file->id);

                            if (! $url) {
                                continue;
                            }

                            $commands[] = EditorCommand::make(
                                'insertContent',
                                arguments: [
                                    [
                                        'type' => 'image',
                                        'attrs' => [
                                            'src' => $url,
                                            'alt' => $file->name,
                                            'title' => $file->name,
                                            'id' => $file->id,
                                        ],
                                    ],
                                ],
                            );
                        } else {
                            $commands[] = EditorCommand::make(
                                'insertContent',
                                arguments: [
                                    [
                                        'type' => 'mediaFile',
                                        'attrs' => [
                                            'id' => $file->id,
                                            'name' => $file->name,
                                            'extension' => $file->extension,
                                            'size' => $file->size,
                                        ],
                                    ],
                                ],
                            );
                        }
                    }

                    $component->runCommands($commands, $arguments['editorSelection'] ?? null);
                }),
        ];
    }

    public function getEnabledToolbarButtons(): array
    {
        return ['mediaLibrary'];
    }

    public function getDisabledToolbarButtons(): array
    {
        return [];
    }
}
