@php
    $path = $parentIndex === null ? "items.{$index}" : "items.{$parentIndex}.children.{$index}";
    $isExpanded = in_array($item['_key'], $expandedKeys, true);
@endphp
<div wire:key="menu-item-{{ $item['_key'] }}" x-data="{ expanded: @js($isExpanded) }" style="margin-bottom: 7px;">
    <div style="border: 1px solid var(--gray-300); border-radius: 3px; background: var(--gray-50); box-shadow: 0 1px 1px rgba(0,0,0,0.04);">
        <div
            draggable="true"
            x-on:dragstart="startDrag($event, @js($item['_key']), @js($parentIndex))"
            style="display: flex; align-items: center; min-height: 42px; padding: 6px 10px; cursor: grab;"
        >
            <button type="button" x-on:click="expanded = !expanded" style="display: flex; align-items: center; justify-content: space-between; gap: 16px; width: 100%; padding: 0; border: 0; background: transparent; text-align: left; cursor: pointer;">
                <strong style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: var(--gray-950); font-size: 14px;">
                    {{ $item['label'] !== '' ? $item['label'] : 'Untitled menu item' }}
                    @if($parentIndex !== null)
                        <span style="color: var(--gray-600); font-size: 12px; font-weight: 400;">sub item</span>
                    @endif
                </strong>
                <span style="display: inline-flex; align-items: center; gap: 12px; flex-shrink: 0; color: var(--gray-600); font-size: 12px; font-weight: 400;">
                    <span>{{ $item['link_type'] === 'page' ? 'Page' : 'Custom Link' }}</span>
                    <span x-text="expanded ? '▾' : '▸'" style="font-size: 14px;">▸</span>
                </span>
            </button>
        </div>

        <div x-show="expanded" x-cloak style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; padding: 16px; border-top: 1px solid var(--gray-200); background: var(--gray-100);">
            <div>
                <label style="display: block; margin-bottom: 5px; color: var(--gray-800); font-size: 12px; font-weight: 600;">Navigation Label</label>
                <input type="text" wire:model.live="{{ $path }}.label" placeholder="Menu item label" style="width: 100%; padding: 7px 9px; border: 1px solid var(--gray-400); border-radius: 3px; font-size: 13px; background: var(--gray-50);" />
            </div>
            <div>
                <label style="display: block; margin-bottom: 5px; color: var(--gray-800); font-size: 12px; font-weight: 600;">Link Type</label>
                <select wire:change="setLinkType(@js($item['_key']), $event.target.value, @js($parentIndex))" style="width: 100%; padding: 7px 9px; border: 1px solid var(--gray-400); border-radius: 3px; font-size: 13px; background: var(--gray-50);">
                    <option value="url" @selected($item['link_type'] === 'url')>Custom Link</option>
                    <option value="page" @selected($item['link_type'] === 'page')>Page</option>
                </select>
            </div>
            <div>
                @if($item['link_type'] === 'page')
                    <label style="display: block; margin-bottom: 5px; color: var(--gray-800); font-size: 12px; font-weight: 600;">Page</label>
                    <select wire:change="selectPage(@js($item['_key']), $event.target.value, @js($parentIndex))" style="width: 100%; padding: 7px 9px; border: 1px solid var(--gray-400); border-radius: 3px; font-size: 13px; background: var(--gray-50);">
                        <option value="">— Select a page —</option>
                        @foreach($pages as $slug => $title)
                            <option value="{{ $slug }}" @selected($item['page_slug'] === $slug)>{{ $title }} (/pages/{{ $slug }})</option>
                        @endforeach
                    </select>
                @else
                    <label style="display: block; margin-bottom: 5px; color: var(--gray-800); font-size: 12px; font-weight: 600;">URL</label>
                    <input type="text" wire:model.live="{{ $path }}.url" placeholder="https://example.com or /pages/news" style="width: 100%; padding: 7px 9px; border: 1px solid var(--gray-400); border-radius: 3px; font-size: 13px; background: var(--gray-50);" />
                @endif
            </div>
            <div>
                <label style="display: block; margin-bottom: 5px; color: var(--gray-800); font-size: 12px; font-weight: 600;">Open Link In</label>
                <select wire:model.live="{{ $path }}.target" style="width: 100%; padding: 7px 9px; border: 1px solid var(--gray-400); border-radius: 3px; font-size: 13px; background: var(--gray-50);">
                    <option value="_self">Same tab</option>
                    <option value="_blank">New tab</option>
                </select>
            </div>
            <div style="grid-column: 1 / -1; display: flex; align-items: center; gap: 12px; padding-top: 8px; border-top: 1px solid var(--gray-200);">
                <button type="button" wire:click="moveRelative(@js($item['_key']), @js($parentIndex), -1)" style="padding: 4px 8px; background: var(--gray-50); color: var(--primary-600); border: 1px solid var(--gray-300); border-radius: 3px; font-size: 12px; cursor: pointer;">Move Up</button>
                <button type="button" wire:click="moveRelative(@js($item['_key']), @js($parentIndex), 1)" style="padding: 4px 8px; background: var(--gray-50); color: var(--primary-600); border: 1px solid var(--gray-300); border-radius: 3px; font-size: 12px; cursor: pointer;">Move Down</button>
                @if($parentIndex === null)
                    <button type="button" wire:click="addChild({{ $index }})" style="padding: 4px 8px; background: var(--gray-50); color: var(--primary-600); border: 1px solid var(--gray-300); border-radius: 3px; font-size: 12px; cursor: pointer;">+ Add Child</button>
                @endif
                <button type="button" wire:click="removeItem(@js($item['_key']), @js($parentIndex))" style="margin-left: auto; padding: 4px 8px; background: transparent; color: var(--danger-600); border: 0; font-size: 12px; text-decoration: underline; cursor: pointer;">Remove</button>
            </div>
        </div>
    </div>

    @if($parentIndex === null && ! empty($item['children']))
        <div x-on:dragover.prevent x-on:drop="dropOnItem($event, null, {{ $index }})" style="margin-left: 34px; padding-top: 7px;">
            @foreach($item['children'] as $childIndex => $child)
                @include('filament-page-builder::livewire.partials.menu-builder-item', [
                    'item' => $child,
                    'index' => $childIndex,
                    'parentIndex' => $index,
                    'pages' => $pages,
                    'expandedKeys' => $expandedKeys,
                ])
            @endforeach
        </div>
    @endif
</div>
