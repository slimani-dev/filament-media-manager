@php
    use Slimani\MediaManager\Models\File;
    use Illuminate\Support\Number;

    $file = $getFileRecord();
    $isImage = $file && str($file->mime_type)->startsWith('image/');
    $limit = $getLimit();
    $state = $getState();
@endphp

<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">
    <div
        @class([
            'fi-in-image flex border-none bg-transparent flex-wrap gap-2',
        ])
    >
        @forelse ($getFileRecords() as $file)
            @php
                $isImage = str($file->mime_type)->startsWith('image/');
                $isVideo = str($file->mime_type)->startsWith('video/');
            @endphp

            <div
                @if ($getAction())
                    x-on:click.stop="$wire.mountAction('preview', { url: @js($file->getUrl()) }, { schemaComponent: @js($getKey()) })"
                    role="button"
                @endif
                @class([
                    'relative group overflow-hidden border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-gray-900 transition-all duration-200',
                    'rounded-full' => $isCircular(),
                    'rounded-xl' => ! $isCircular(),
                    'aspect-square' => $isSquare() || $isCircular(),
                    'hover:ring-2 hover:ring-primary-500/50 hover:border-primary-500/50 cursor-pointer' => $getAction(),
                ])
                style="width: {{ $getWidth() ?? '100px' }}; height: {{ $getHeight() ?? '100px' }};"
            >
                @if ($isImage || $isVideo)
                    <img
                        src="{{ $file->getUrl($isVideo ? 'thumb' : $getConversion()) }}"
                        alt="{{ $file->name }}"
                        @class([
                            'h-full w-full object-cover transition-transform duration-300 group-hover:scale-110',
                        ])
                        loading="lazy"
                    />

                    @if ($isVideo)
                        <div class="absolute inset-0 z-10 flex items-center justify-center pointer-events-none">
                             <x-heroicon-s-play-circle class="h-8 w-8 text-white/70 drop-shadow-lg transition-transform duration-300 group-hover:scale-110 group-hover:text-white/90" />
                        </div>
                    @endif
                @else
                    <div class="flex h-full w-full flex-col items-center justify-center gap-2 p-2">
                        <div class="relative transition-transform duration-300 group-hover:scale-110">
                            <x-heroicon-s-document-text class="fi-in-document-text h-10 w-10 text-gray-400 dark:text-gray-500" />
                            <span class="absolute -bottom-1 -right-1 rounded bg-gray-500 px-1 py-0.5 text-[8px] font-bold uppercase text-white ring-1 ring-white dark:ring-gray-800">
                                {{ str($file->extension)->upper() ?? 'FILE' }}
                            </span>
                        </div>
                        <span class="max-w-full truncate px-1 text-[10px] font-bold text-gray-700 dark:text-gray-200" title="{{ $file->name }}">
                            {{ $file->name }}
                        </span>
                        <span class="max-w-full truncate px-1 text-[8px] font-medium text-gray-500 dark:text-gray-400">
                            {{ Number::fileSize($file->size ?? 0) }}
                        </span>
                    </div>
                @endif

                <!-- Hover Overlay -->
                @if ($getAction())
                    <div class="absolute inset-0 z-10 flex items-center justify-center bg-primary-500/10 opacity-0 transition-opacity duration-200 group-hover:opacity-100">
                        <x-heroicon-m-eye class="h-6 w-6 text-primary-600 dark:text-primary-400" />
                    </div>
                @endif
            </div>
        @empty
            @if ($placeholder = $getPlaceholder())
                <div class="fi-in-placeholder text-sm text-gray-400 dark:text-gray-500">
                    {{ $placeholder }}
                </div>
            @endif
        @endforelse
    </div>
</x-dynamic-component>
