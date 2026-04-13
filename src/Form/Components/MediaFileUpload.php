<?php

namespace Slimani\MediaManager\Form\Components;

use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Model;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Slimani\MediaManager\Models\File;

class MediaFileUpload extends FileUpload
{
    protected string|Closure|null $collection = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->getUploadedFileUsing(static function (MediaFileUpload $component, $file): ?array {
            if (! $file) {
                return null;
            }

            /** @var File|null $fileModel */
            $fileModel = File::find($file);

            if (! $fileModel) {
                return null;
            }

            return [
                'name' => $fileModel->name,
                'size' => $fileModel->size,
                'type' => $fileModel->mime_type,
                'url' => $fileModel->getUrl(),
            ];
        });

        $this->saveUploadedFileUsing(static function (MediaFileUpload $component, TemporaryUploadedFile $file, ?Model $record): ?string {
            $fileModel = File::create([
                'name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'uploaded_by_user_id' => auth()->id(),
            ]);

            $media = $fileModel->addMediaFromString($file->get())
                ->usingFileName($file->getClientOriginalName())
                ->toMediaCollection('default');

            $fileModel->update([
                'name' => $media->file_name,
                'size' => $media->size,
                'mime_type' => $media->mime_type,
                'extension' => $media->extension,
                'width' => $media->getCustomProperty('width'),
                'height' => $media->getCustomProperty('height'),
            ]);

            return (string) $fileModel->id;
        });

        $this->hintAction(
            Action::make('browse')
                ->label(__('media-manager::media-manager.actions.browse_media'))
                ->icon('heroicon-m-folder')
                ->modalContent(fn (MediaFileUpload $component) => view(/** @var view-string */ 'media-manager::forms.components.media-browser-modal', [
                    'statePath' => $component->getStatePath(),
                ]))
                ->modalSubmitAction(false)
                ->modalCancelAction(false)
                ->extraModalWindowAttributes(['class' => 'media-browser-modal'])
        );

        $this->registerActions([
            Action::make('selectMedia')
                ->hidden()
                ->action(function (array $data, MediaFileUpload $component) {
                    $component->state($data['file']);
                }),
        ]);
    }

    public function collection(string|Closure|null $collection): static
    {
        $this->collection = $collection;

        return $this;
    }

    public function getCollection(): ?string
    {
        return $this->evaluate($this->collection);
    }
}
