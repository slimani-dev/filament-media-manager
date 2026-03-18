@php
    use Illuminate\Support\Number;
@endphp
<a href="{{ $url }}" target="_blank" class="fi-media-file-node not-prose flex items-center gap-3 p-3 rounded-xl border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-gray-900 transition-all duration-200 hover:ring-2 hover:ring-primary-500/50 hover:border-primary-500/50 my-4" style="text-decoration: none;">
    <div class="relative flex-shrink-0">
        <x-heroicon-s-document-text class="h-10 w-10 text-gray-400 dark:text-gray-500" />
        <span class="absolute -bottom-1 -right-1 rounded bg-gray-500 px-1 py-0.5 text-[8px] font-bold uppercase text-white ring-1 ring-white dark:ring-gray-800">
            {{ strtoupper($extension ?? 'FILE') }}
        </span>
    </div>
    <div class="flex flex-col min-w-0">
        <span class="text-sm font-bold text-gray-700 dark:text-gray-200 truncate" title="{{ $name }}">
            {{ $name }}
        </span>
        <span class="text-[10px] font-medium text-gray-500 dark:text-gray-400">
            {{ Number::fileSize($size ?? 0) }}
        </span>
    </div>
</a>
