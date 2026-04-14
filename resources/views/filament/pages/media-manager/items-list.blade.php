<div>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
        {{-- Folders --}}
        @foreach ($folders as $folder)
            <a href="{{ \Slimani\MediaManager\Pages\MediaManager::getFolderUrl($folder->id) }}" wire:navigate
               class="group relative cursor-pointer bg-white dark:bg-gray-800 border border-gray-200 dark:border-white/10 hover:border-primary-500 rounded-xl overflow-hidden shadow-sm transition-all">
                <div
                    class="aspect-square bg-gray-100 dark:bg-gray-900 w-full overflow-hidden flex items-center justify-center relative">
                    <x-heroicon-s-folder class="w-12 h-12 text-amber-400 mb-2" />
                </div>
                <div class="p-3">
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-200 truncate group-hover:text-primary-600"
                       title="{{ $folder->name }}">
                        {{ $folder->name }}</p>
                    <span class="text-xs text-gray-400 mt-1">
                       {{ trans_choice('media-manager::media-manager.common.item_count', $folder->children_count + $folder->files_count, ['count' => $folder->children_count + $folder->files_count]) }}
                    </span>

                </div>
            </a>
        @endforeach

        {{-- Files --}}
        @foreach ($mediaFiles as $file)
            <div wire:click="selectFile({{ $file->id }})"
                 class="group relative cursor-pointer bg-white dark:bg-gray-800 border {{ $selectedFileId === $file->id ? 'border-primary-500 ring-2 ring-primary-500/20' : 'border-gray-200 dark:border-white/10 hover:border-primary-500' }} rounded-xl overflow-hidden shadow-sm transition-all">
                <div
                    class="aspect-square bg-gray-100 dark:bg-gray-900 w-full overflow-hidden flex items-center justify-center relative">
                    @if ($file->getUrl('thumb'))
                        <img src="{{ $file->getUrl('thumb') }}" alt="{{ $file->alt_text }}"
                             class="object-cover w-full h-full group-hover:scale-105 transition-transform duration-300">
                    @else
                        <x-heroicon-o-document class="w-10 h-10 text-gray-400" />
                    @endif
                </div>
                <div class="p-3 {{ $selectedFileId === $file->id ? 'bg-primary-50 dark:bg-primary-900/10' : '' }}">
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-200 truncate group-hover:text-primary-600"
                       title="{{ $file->name }}">
                        {{ $file->name }}</p>
                </div>
            </div>
        @endforeach
    </div>

    @if ($folders->isEmpty() && $mediaFiles->isEmpty())
        <div class="flex flex-col items-center justify-center py-12 text-gray-400 dark:text-gray-500">
            <x-heroicon-o-folder-open class="w-16 h-16 mb-4 opacity-50" />
            <p>{{ __('media-manager::media-manager.messages.empty_folder') }}</p>
        </div>
    @endif
</div>
