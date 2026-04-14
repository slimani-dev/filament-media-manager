@php
    $count = count($breadcrumbs);
    $visibleCount = 3; // Show current + 2 parents max
    $hasHidden = $count > $visibleCount;
@endphp

<div class="flex-1 min-w-0 flex flex-wrap sm:flex-nowrap items-center gap-1 text-sm font-medium text-gray-500 dark:text-gray-400">
    {{-- Root / Home --}}
    <button
        type="button"
        wire:click.stop="setCurrentFolder(null)"
        class="flex items-center justify-center p-1 rounded-md hover:bg-gray-100 hover:text-primary-600 dark:hover:bg-gray-800 dark:hover:text-primary-400 transition-colors shrink-0"
    >
        <x-heroicon-s-home class="w-5 h-5 text-gray-400 dark:text-gray-500 group-hover:text-primary-600 dark:group-hover:text-primary-400" />
    </button>

    @if($count > 0)

        {{-- Ellipsis Dropdown for hidden items --}}
        @if($hasHidden)
            <div x-data="{ dropdownOpen: false }" class="relative flex items-center shrink-0">
                <x-heroicon-m-chevron-right class="w-4 h-4 mx-1 text-gray-300 dark:text-gray-600 flex-shrink-0" />

                <button
                    x-on:click="dropdownOpen = !dropdownOpen"
                    x-on:click.outside="dropdownOpen = false"
                    class="p-1 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-400 font-bold focus:outline-none focus:ring-2 focus:ring-primary-500"
                    title="{{ __('media-manager::media-manager.messages.show_hidden_folders') }}"
                >
                    <x-heroicon-m-ellipsis-horizontal class="w-5 h-5" />
                </button>

                <!-- Dropdown Menu -->
                <div
                    x-show="dropdownOpen"
                    x-transition.opacity
                    class="absolute top-10 left-4 mt-1 min-w-[12rem] max-w-sm bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg z-50 py-1 overflow-hidden"
                    x-cloak
                >
                    <div class="flex flex-col max-h-64 overflow-y-auto w-full">
                        @foreach($breadcrumbs as $index => $breadcrumb)
                            @if($index < $count - $visibleCount)
                                <button
                                    type="button"
                                    wire:click.stop="setCurrentFolder({{ $breadcrumb['id'] }})"
                                    class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-primary-600 dark:hover:text-primary-400 truncate"
                                    title="{{ $breadcrumb['name'] }}"
                                >
                                    {{ $breadcrumb['name'] }}
                                </button>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        {{-- Visible Folders (Last 3) --}}
        @foreach($breadcrumbs as $index => $breadcrumb)
            @if($index >= $count - $visibleCount)
                <div class="flex items-center min-w-0 shrink">
                    <x-heroicon-m-chevron-right class="w-4 h-4 mx-1 text-gray-300 dark:text-gray-600 flex-shrink-0" />

                    <button
                        type="button"
                        wire:click.stop="setCurrentFolder({{ $breadcrumb['id'] }})"
                        class="truncate max-w-[120px] sm:max-w-[150px] lg:max-w-[200px] px-1 hover:text-primary-600 dark:hover:text-primary-400 transition-colors {{ $loop->last ? 'text-gray-900 dark:text-gray-100 font-semibold cursor-default pointer-events-none' : '' }}"
                        title="{{ $breadcrumb['name'] }}"
                    >
                        {{ $breadcrumb['name'] }}
                    </button>
                </div>
            @endif
        @endforeach
    @endif
</div>
