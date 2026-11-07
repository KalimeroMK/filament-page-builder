<div
    x-data="{
        draggedKey: null,
        draggedParent: null,
        startDrag(event, key, parent) {
            this.draggedKey = key;
            this.draggedParent = parent;
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', key);
        },
        dropOnItem(event, targetKey, targetParent) {
            event.preventDefault();
            event.stopPropagation();
            if (this.draggedKey) {
                $wire.moveItem(this.draggedKey, this.draggedParent, targetKey, targetParent);
            }
            this.draggedKey = null;
            this.draggedParent = null;
        }
    }"
    style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;"
>
    <style>
        .fpb-menu-builder-layout { display: grid; grid-template-columns: 320px minmax(0, 1fr); gap: 24px; align-items: start; }
        @media (max-width: 900px) { .fpb-menu-builder-layout { grid-template-columns: minmax(0, 1fr); } }
    </style>

    <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; max-width: 720px; margin-bottom: 22px;">
        <div>
            <label for="fpb-menu-title" style="display: block; margin-bottom: 5px; font-size: 13px; color: var(--gray-800);">Menu Name</label>
            <input id="fpb-menu-title" type="text" wire:model.live="titleData.title" style="width: 100%; padding: 7px 10px; border: 1px solid var(--gray-400); border-radius: 3px; font-size: 14px;" />
            @error('titleData.title') <div style="color: var(--danger-600); font-size: 12px; margin-top: 4px;">{{ $message }}</div> @enderror
        </div>
        <div>
            <label for="fpb-menu-slug" style="display: block; margin-bottom: 5px; font-size: 13px; color: var(--gray-800);">Slug</label>
            <input id="fpb-menu-slug" type="text" wire:model.live="titleData.slug" style="width: 100%; padding: 7px 10px; border: 1px solid var(--gray-400); border-radius: 3px; font-size: 14px;" />
            @error('titleData.slug') <div style="color: var(--danger-600); font-size: 12px; margin-top: 4px;">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="fpb-menu-builder-layout">
        <aside style="border: 1px solid var(--gray-200); background: var(--gray-50);">
            <section style="border-bottom: 1px solid var(--gray-200);">
                <h2 style="margin: 0; padding: 12px 14px; border-bottom: 1px solid var(--gray-200); color: var(--gray-950); font-size: 14px; font-weight: 600;">Pages</h2>
                <div style="display: flex; gap: 16px; padding: 10px 12px; border-bottom: 1px solid var(--gray-200);">
                    @foreach(['recent' => 'Most Recent', 'all' => 'View All', 'search' => 'Search'] as $tab => $label)
                        <button type="button" wire:click="setPanelTab('{{ $tab }}')" style="padding: 0; border: 0; background: transparent; color: {{ $panelTab === $tab ? 'var(--gray-950)' : 'var(--primary-600)' }}; font-size: 12px; font-weight: {{ $panelTab === $tab ? '600' : '400' }}; cursor: pointer;">{{ $label }}</button>
                    @endforeach
                </div>

                @if($panelTab === 'search')
                    <div style="padding: 10px 12px 0;">
                        <input type="search" wire:model.live.debounce.300ms="availablePagesSearch" placeholder="Search pages…" style="width: 100%; padding: 6px 8px; border: 1px solid var(--gray-400); border-radius: 3px; font-size: 13px;" />
                    </div>
                @endif

                <div style="max-height: 540px; overflow-y: auto; padding: 8px 12px;">
                    @forelse($pages as $slug => $title)
                        <label style="display: flex; align-items: flex-start; gap: 8px; padding: 5px 0; color: var(--gray-800); font-size: 13px; cursor: pointer;">
                            <input type="checkbox" wire:model.live="selectedPages" value="{{ $slug }}" style="margin-top: 2px;" />
                            <span>{{ $title }}</span>
                        </label>
                    @empty
                        <p style="padding: 10px 0; color: var(--gray-600); font-size: 13px;">No pages found.</p>
                    @endforelse
                </div>

                <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 10px 12px; border-top: 1px solid var(--gray-200);">
                    <label style="display: inline-flex; align-items: center; gap: 7px; color: var(--gray-800); font-size: 13px;">
                        <input type="checkbox" wire:change="toggleSelectAll($event.target.checked)" @checked($selectAllPages) /> Select All
                    </label>
                    <button type="button" wire:click="addSelectedPages" style="padding: 6px 12px; background: var(--gray-100); color: var(--primary-600); border: 1px solid var(--primary-600); border-radius: 3px; font-size: 12px; cursor: pointer;">
                        Add to Menu
                    </button>
                </div>
            </section>

            <section x-data="{ open: false }">
                <button type="button" x-on:click="open = !open" style="display: flex; align-items: center; justify-content: space-between; width: 100%; padding: 12px 14px; border: 0; background: var(--gray-50); color: var(--gray-950); font-size: 14px; font-weight: 600; cursor: pointer;">
                    <span>Custom Links</span><span x-text="open ? '▾' : '▸'">▸</span>
                </button>
                <div x-show="open" x-cloak style="padding: 0 12px 12px;">
                    <label style="display: block; margin-bottom: 8px; font-size: 12px; color: var(--gray-800);">
                        Label
                        <input type="text" wire:model.live="customLink.label" placeholder="Custom label" style="width: 100%; margin-top: 4px; padding: 6px 8px; border: 1px solid var(--gray-400); border-radius: 3px;" />
                    </label>
                    <label style="display: block; margin-bottom: 8px; font-size: 12px; color: var(--gray-800);">
                        URL
                        <input type="text" wire:model.live="customLink.url" placeholder="https://example.com or /pages/news" style="width: 100%; margin-top: 4px; padding: 6px 8px; border: 1px solid var(--gray-400); border-radius: 3px;" />
                    </label>
                    @error('customLink') <div style="color: var(--danger-600); font-size: 12px; margin-bottom: 8px;">{{ $message }}</div> @enderror
                    <button type="button" wire:click="addCustomLink" style="padding: 6px 12px; background: var(--gray-100); color: var(--primary-600); border: 1px solid var(--primary-600); border-radius: 3px; font-size: 12px; cursor: pointer;">Add Custom Link</button>
                </div>
            </section>
        </aside>

        <main>
            <p style="margin: 0 0 14px; color: var(--gray-800); font-size: 14px;">
                Drag the items into the order you prefer. Click the arrow on the right of the item to reveal additional configuration options.
            </p>

            <div x-on:dragover.prevent x-on:drop="dropOnItem($event, null, null)" style="min-height: 160px; padding: 8px; background: var(--gray-100); border: 1px solid var(--gray-200);">
                @forelse($items as $index => $item)
                    @include('filament-page-builder::livewire.partials.menu-builder-item', [
                        'item' => $item,
                        'index' => $index,
                        'parentIndex' => null,
                        'pages' => $pages,
                        'expandedKeys' => $expandedKeys,
                    ])
                @empty
                    <div style="padding: 36px 18px; text-align: center; color: var(--gray-600); border: 1px dashed var(--gray-400); background: var(--gray-50);">
                        <p style="margin: 0 0 12px;">This menu is empty. Add pages from the left panel or create a custom link.</p>
                        <button type="button" wire:click="addItem" style="padding: 7px 14px; background: var(--primary-600); color: #ffffff; border: 0; border-radius: 3px; cursor: pointer;">Add Menu Item</button>
                    </div>
                @endforelse
            </div>

            <div style="display: flex; align-items: center; gap: 16px; margin-top: 16px;">
                <button type="button" wire:click="save" style="padding: 8px 18px; background: var(--primary-600); color: #ffffff; border: 1px solid var(--primary-600); border-radius: 3px; font-size: 13px; font-weight: 600; cursor: pointer;">
                    Save Menu
                </button>
                <button type="button" wire:click="deleteMenu" wire:confirm="Delete this menu and all of its items?" style="padding: 0; background: transparent; color: var(--danger-600); border: 0; font-size: 13px; text-decoration: underline; cursor: pointer;">
                    Delete Menu
                </button>
            </div>
        </main>
    </div>
</div>
