@php
    use Slimani\MediaManager\Models\Folder;
    use Illuminate\Support\Number;

    /** @var \Slimani\MediaManager\Models\File|\Slimani\MediaManager\Models\Folder $item */
    $item = $item ?? $get('item');
    $isSelected = collect($this->selectedItems ?? [])->contains($item instanceof Folder ? "folder-{$item->id}" : "file-{$item->id}");
    $isFolder = $item instanceof Folder;
@endphp

<div 
    {{ $attributes->class([
        'fi-media-item group',
        'fi-is-disabled' => !($isAccepted ?? true),
    ]) }}
    :class="{ 'fi-is-selected': isSelected }"
    x-data="{ 
        longPressTimeout: null, 
        isSelected: @js($isSelected),
        isLongPress: false, 
        isDragging: false, 
        startPress(e) { 
            if (!{{ $isAccepted ? 'true' : 'false' }}) return;
            this.isDragging = false; 
            this.isLongPress = false; 
            this.longPressTimeout = setTimeout(() => { 
                this.isLongPress = true; 
                this.isSelected = !this.isSelected;
                $wire.toggleSelection('{{ $isFolder ? "folder-" : "file-" }}{{ $item->id }}'); 
                if ('vibrate' in navigator) navigator.vibrate(50); 
            }, 500); 
        }, 
        cancelPress() { 
            clearTimeout(this.longPressTimeout); 
        },
        handleSingleClick() {
            if (this.isLongPress) return;
            
            console.log('Media Item Single Click - Type: {{ $isFolder ? "Folder" : "File" }}, ID: {{ $item->id }}');
            @if($isFolder)
                $wire.setCurrentFolder({{ $item->id }})
            @else
                if (!{{ $isAccepted ? 'true' : 'false' }}) return;
                
                this.isSelected = !this.isSelected;
                
                $wire.selectFile({{ $item->id }});
                if (!$wire.isPicker) {
                    $wire.showDetails = true;
                }
            @endif
        }
    }"
    x-on:mousedown="startPress"
    x-on:touchstart.passive="startPress"
    x-on:mouseup="cancelPress"
    x-on:mouseleave="cancelPress"
    x-on:touchend="cancelPress"
    x-on:touchmove.passive="isDragging = true; cancelPress()"
    x-on:contextmenu.prevent=""
    x-on:click.capture="if (isLongPress) { $event.stopPropagation(); $event.preventDefault(); isLongPress = false; }"
>
    <!-- Thumbnail Area -->
    <div class="fi-media-item-thumbnail-container">
        @if($isFolder)
            <div class="relative transition-transform duration-300 group-hover:scale-110">
                <x-heroicon-s-folder class="fi-media-item-folder-icon" />
                <div class="absolute inset-x-0 bottom-2 flex justify-center">
                    <span class="px-1.5 py-0.5 rounded-md bg-amber-500/10 text-amber-700 dark:text-amber-400 text-[10px] font-bold">
                        {{ $item->children_count + $item->files_count }}
                    </span>
                </div>
            </div>
        @else
            @if(str($item->mime_type)->startsWith('image/'))
                <img 
                    src="{{ $item->getUrl('thumb') }}" 
                    alt="{{ $item->name }}" 
                    class="fi-media-item-file-image"
                    loading="lazy"
                >
            @else
                <div class="flex flex-col items-center gap-2 transition-transform duration-300 group-hover:scale-110">
                    <div class="relative">
                        <x-heroicon-s-document-text class="fi-media-item-file-icon" />
                        <span class="absolute bottom-1 right-0 px-1 py-0.5 rounded bg-gray-500 text-white text-[8px] font-bold uppercase ring-1 ring-white dark:ring-gray-800">
                            {{ $item->extension ?? 'FILE' }}
                        </span>
                    </div>
                </div>
            @endif
        @endif

        <!-- Selection Badge -->
        <div 
            :class="isSelected ? 'scale-100 opacity-100' : 'scale-75 opacity-0 group-hover:opacity-100'"
            class="fi-media-item-selection-badge group-selection"
        >
            <button 
                type="button"
                @if(!$isAccepted) disabled @endif
                x-on:click.stop="isSelected = !isSelected; $wire.toggleSelection('{{ $isFolder ? "folder-" : "file-" }}{{ $item->id }}')"
                class="fi-media-item-selection-button"
            >
                <x-heroicon-m-check class="w-4 h-4" />
            </button>
        </div>

        <!-- Selection Overlay (Mobile friendly tap target) -->
        <div class="absolute inset-0 z-10 cursor-pointer" 
             x-on:click.stop="handleSingleClick">
        </div>
    </div>

    <!-- Info Area -->
    <div class="fi-media-item-info">
        <div class="min-w-0">
            <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate flex items-center gap-1.5" title="{{ $item->name }}">
                @if($isFolder)
                    <x-heroicon-m-folder class="w-3.5 h-3.5 text-amber-500" />
                @else
                    <x-heroicon-m-document class="w-3.5 h-3.5 text-gray-400" />
                @endif
                <span class="truncate">{{ $item->name }}</span>
            </h4>
            
            <div class="mt-1 flex items-center justify-between text-[11px] text-gray-500 dark:text-gray-400 font-medium">
                @if($isFolder)
                    <span>{{ $item->children_count + $item->files_count }} items</span>
                @else
                    <span>{{ Number::fileSize($item->size ?? 0) }}</span>
                    <span class="uppercase tracking-wider opacity-60">{{ $item->extension }}</span>
                @endif
            </div>
        </div>
    </div>

    <!-- Actions (Shown on hover) -->
    <div class="absolute inset-x-0 bottom-0 p-2 translate-y-2 opacity-0 group-hover:translate-y-0 group-hover:opacity-100 transition-all duration-200 z-30 pointer-events-none">
        <div class="flex justify-center gap-1 pointer-events-auto">
             <!-- Future actions like Quick View, Download, etc could go here -->
        </div>
    </div>
</div>
