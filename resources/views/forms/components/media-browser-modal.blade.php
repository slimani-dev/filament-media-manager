<div class="flex flex-col h-full" x-data="{
    selectedFile: null,
    submit() {
        if (this.selectedFile) {
            // Call the hidden action 'selectMedia' on the component
            $wire.mountFormComponentAction('{{ $statePath }}', 'selectMedia', { file: this.selectedFile });
            // We should also close the modal, but the action might handle it or we can dispatch close-modal
            $dispatch('close-modal', { id: '{{ $this->getId() }}' });
        }
    }
}"
    x-on:media-picker-selected.stop="selectedFile = $event.detail.state">
    <div class="flex-1 overflow-hidden min-h-[400px]">
        @livewire('media-browser', ['isPicker' => true])
    </div>

    <div class="flex items-center justify-end gap-x-3 p-4 border-t dark:border-gray-700">
        <x-filament::button color="gray" x-on:click="$dispatch('close-modal', { id: '{{ $this->getId() }}' })">
            {{ __('media-manager::media-manager.actions.cancel') }}
        </x-filament::button>

        <x-filament::button x-on:click="submit" x-bind:disabled="!selectedFile">
            {{ __('media-manager::media-manager.actions.select') }}
        </x-filament::button>
    </div>
</div>
