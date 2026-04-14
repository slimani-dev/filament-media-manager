<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div x-data="{
        state: $wire.$entangle('{{ $getStatePath() }}'),
        isOpen: false,
        pickerId: '{{ $getId() }}',
        selectedFile: @js($field->getFile() ? ['url' => $field->getFile()->getUrl('preview'), 'name' => $field->getFile()->name] : null),

        openModal() {
            this.isOpen = true;
            $dispatch('open-modal', { id: 'media-picker-modal-' + this.pickerId });
        },
        closeModal() {
            this.isOpen = false;
            $dispatch('close-modal', { id: 'media-picker-modal-' + this.pickerId });
        },
        handleSelection(event) {
            if (event.detail.pickerId === this.pickerId) {
                this.state = event.detail.state;
                this.selectedFile = {
                    url: event.detail.file.url || event.detail.file.preview_url,
                    name: event.detail.file.name
                };
            }
        }
    }" x-on:media-picker-selected.window="handleSelection($event)"
        x-on:close-modal.window="if ($event.detail.id === 'media-picker-modal-' + pickerId) isOpen = false">
        <!-- Preview -->
        <div x-show="selectedFile" class="mb-3 block">
            <div
                class="relative w-full h-64 bg-gray-50 rounded-lg overflow-hidden border border-gray-200 dark:bg-gray-800 dark:border-gray-700 flex items-center justify-center">
                <img :src="selectedFile?.url" class="w-full h-full object-contain" />
                <button type="button" @click="state = null; selectedFile = null"
                    class="absolute top-2 right-2 bg-white/80 rounded-full p-1 text-gray-500 hover:text-red-500 shadow-sm transition">
                    <x-heroicon-s-x-mark class="w-5 h-5" />
                </button>
            </div>
        </div>

        <!-- Trigger Button -->
        <div x-show="!selectedFile">
            <x-filament::button @click="openModal" color="gray">
                {{ __('media-manager::media-manager.actions.select_file') }}
            </x-filament::button>
        </div>

        <div x-show="selectedFile">
            <x-filament::button @click="openModal" color="gray" size="sm">
                {{ __('media-manager::media-manager.actions.change_file') }}
            </x-filament::button>
        </div>

        <!-- Modal -->
        <x-filament::modal :id="'media-picker-modal-' . $getId()" width="5xl" :close-by-clicking-away="false">
            <template x-if="isOpen">
                <div class="h-[80vh] overflow-y-auto p-1">
                    @livewire('media-browser', ['isPicker' => true, 'pickerId' => $getId(), 'pickedFiles' => []])
                </div>
            </template>
        </x-filament::modal>
    </div>
</x-dynamic-component>
