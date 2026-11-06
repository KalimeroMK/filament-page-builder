@php
    $viewportMaxWidth = match ($viewport) {
        'tablet' => '1024px',
        'mobile' => '375px',
        default => '100%',
    };
@endphp
<div
    class="fpb-visual-editor"
    x-data="{
        draggedElementId: null,
        draggedWidgetType: null,
        handleWidgetDragStart(event, type) {
            this.draggedWidgetType = type;
            this.draggedElementId = null;
            event.dataTransfer.effectAllowed = 'copy';
        },
        handleElementDragStart(event, id) {
            event.stopPropagation();
            this.draggedElementId = id;
            this.draggedWidgetType = null;
            event.dataTransfer.effectAllowed = 'move';
        },
        handleDropOnContainer(event, containerId) {
            event.preventDefault();
            event.stopPropagation();
            if (this.draggedWidgetType) {
                $wire.addWidget(this.draggedWidgetType, containerId);
                this.draggedWidgetType = null;
            }
        }
    }"
    style="display: flex; flex-direction: column; min-height: 82vh; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; background: #f1f5f9;"
>
    <style>{!! $generatedCss !!}</style>

    {{-- Top Editor Toolbar: Page Status + Viewport Switcher + Save Draft / Preview / Publish --}}
    <header style="display: flex; align-items: center; justify-content: space-between; padding: 12px 20px; background: #0f172a; color: #f8fafc; border-bottom: 1px solid #1e293b; gap: 16px; flex-wrap: wrap;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <span style="font-weight: 700; font-size: 15px;">{{ $pageSettingsData['title'] ?? $page->title }}</span>
            <span style="padding: 2px 8px; border-radius: 9999px; font-size: 11px; font-weight: 600; background: {{ $page->isPublished() ? '#065f46' : '#78350f' }}; color: #fff;">
                {{ $page->status->getLabel() }}
            </span>
            @if($hasUnsavedEditorChanges)
                <span style="color: #fbbf24; font-size: 12px;">• Unsaved editor changes</span>
            @elseif($page->hasUnpublishedChanges())
                <span style="color: #38bdf8; font-size: 12px;">• Draft ahead of Published</span>
            @else
                <span style="color: #34d399; font-size: 12px;">• Synced</span>
            @endif
        </div>

        {{-- Responsive Viewport Switcher --}}
        <div style="display: flex; align-items: center; gap: 6px; background: #1e293b; padding: 4px; border-radius: 8px;">
            @foreach(['desktop' => 'Desktop', 'tablet' => 'Tablet (1024px)', 'mobile' => 'Mobile (375px)'] as $vpKey => $vpLabel)
                <button
                    type="button"
                    wire:click="setViewport('{{ $vpKey }}')"
                    style="padding: 5px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; border: 0; cursor: pointer; background: {{ $viewport === $vpKey ? '#2563eb' : 'transparent' }}; color: #f8fafc;"
                >
                    {{ $vpLabel }}
                </button>
            @endforeach
        </div>

        {{-- Draft / Preview / Publish Actions --}}
        <div style="display: flex; align-items: center; gap: 8px;">
            @if($page->hasUnpublishedChanges() && $page->content !== null)
                <button
                    type="button"
                    wire:click="discardDraft"
                    wire:confirm="Discard unpublished draft changes and revert to published version?"
                    style="padding: 7px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; background: #334155; color: #f8fafc; border: 1px solid #475569; cursor: pointer;"
                >
                    Discard Draft
                </button>
            @endif

            <button
                type="button"
                wire:click="saveDraft"
                style="padding: 7px 14px; border-radius: 6px; font-size: 12px; font-weight: 600; background: #1e293b; color: #f8fafc; border: 1px solid #475569; cursor: pointer;"
            >
                Save Draft
            </button>

            <a
                href="{{ $previewUrl }}"
                target="_blank"
                rel="noopener noreferrer"
                style="padding: 7px 14px; border-radius: 6px; font-size: 12px; font-weight: 600; background: #334155; color: #f8fafc; text-decoration: none; border: 1px solid #475569;"
            >
                Preview
            </a>

            <button
                type="button"
                wire:click="publishPage"
                style="padding: 7px 16px; border-radius: 6px; font-size: 12px; font-weight: 700; background: #16a34a; color: #ffffff; border: 0; cursor: pointer;"
            >
                Publish
            </button>
        </div>
    </header>

    {{-- Two-Column Workspace: Left Panel + Right Canvas --}}
    <div style="display: flex; flex: 1; min-height: 75vh;">
        {{-- Left Sidebar Panel --}}
        <aside style="width: 350px; min-width: 350px; background: #ffffff; border-right: 1px solid #e2e8f0; display: flex; flex-direction: column;">
            {{-- Sidebar Mode Switcher --}}
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); border-bottom: 1px solid #e2e8f0; background: #f8fafc;">
                @foreach(['widgets' => '+ Elements', 'inspector' => 'Inspector', 'page_settings' => 'Page SEO'] as $tabKey => $tabLabel)
                    <button
                        type="button"
                        wire:click="setLeftTab('{{ $tabKey }}')"
                        style="padding: 10px 8px; font-size: 12px; font-weight: 600; border: 0; border-bottom: 2px solid {{ $leftTab === $tabKey ? '#2563eb' : 'transparent' }}; background: {{ $leftTab === $tabKey ? '#ffffff' : 'transparent' }}; color: {{ $leftTab === $tabKey ? '#2563eb' : '#475569' }}; cursor: pointer;"
                    >
                        {{ $tabLabel }}
                    </button>
                @endforeach
            </div>

            <div style="padding: 16px; overflow-y: auto; flex: 1;">
                {{-- TAB 1: Widgets Palette --}}
                @if($leftTab === 'widgets')
                    @if($targetContainerId !== null)
                        <div style="margin-bottom: 12px; padding: 8px 10px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px; font-size: 12px; display: flex; align-items: center; justify-content: space-between;">
                            <span>Target Container: <strong>{{ $targetContainerId }}</strong></span>
                            <button type="button" wire:click="setTargetContainer(null)" style="border: 0; background: transparent; color: #2563eb; cursor: pointer; font-size: 11px; font-weight: 600;">
                                Reset to Root
                            </button>
                        </div>
                    @endif

                    @foreach($widgetsByCategory as $category => $widgets)
                        <div style="margin-bottom: 18px;">
                            <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; margin-bottom: 8px;">
                                {{ ucfirst($category) }} Elements
                            </div>
                            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px;">
                                @foreach($widgets as $type => $widget)
                                    <button
                                        type="button"
                                        draggable="true"
                                        x-on:dragstart="handleWidgetDragStart($event, '{{ $type }}')"
                                        wire:click="addWidget('{{ $type }}')"
                                        data-widget-type="{{ $type }}"
                                        style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px; padding: 12px 8px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; cursor: pointer; font-size: 12px; font-weight: 600; color: #1e293b;"
                                    >
                                        <span>{{ $widget::getLabel() }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                @endif

                {{-- TAB 2: Active Element Inspector (Content / Style / Advanced via Filament v5 Schema) --}}
                @if($leftTab === 'inspector')
                    @if($activeElement !== null)
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; padding-bottom: 10px; border-bottom: 1px solid #e2e8f0;">
                            <div>
                                <div style="font-size: 13px; font-weight: 700; color: #0f172a;">
                                    Editing: {{ ucfirst((string) ($activeElement['type'] ?? 'Element')) }}
                                </div>
                                <div style="font-size: 11px; color: #64748b;">ID: {{ $activeElement['id'] }}</div>
                            </div>
                            <div style="display: flex; gap: 4px;">
                                <button type="button" wire:click="moveElementUp('{{ $activeElement['id'] }}')" title="Move Up" style="padding: 4px 8px; font-size: 11px; border: 1px solid #cbd5e1; border-radius: 4px; background: #f8fafc; cursor: pointer;">↑</button>
                                <button type="button" wire:click="moveElementDown('{{ $activeElement['id'] }}')" title="Move Down" style="padding: 4px 8px; font-size: 11px; border: 1px solid #cbd5e1; border-radius: 4px; background: #f8fafc; cursor: pointer;">↓</button>
                                <button type="button" wire:click="duplicateElement('{{ $activeElement['id'] }}')" title="Duplicate" style="padding: 4px 8px; font-size: 11px; border: 1px solid #cbd5e1; border-radius: 4px; background: #f8fafc; cursor: pointer;">Copy</button>
                                <button type="button" wire:click="removeElement('{{ $activeElement['id'] }}')" title="Delete" style="padding: 4px 8px; font-size: 11px; border: 1px solid #fecaca; border-radius: 4px; background: #fef2f2; color: #dc2626; cursor: pointer;">Del</button>
                            </div>
                        </div>

                        <div wire:key="inspector-schema-{{ $activeElement['id'] }}">
                            {{ $this->inspectorSchema }}
                        </div>
                    @else
                        <div style="text-align: center; padding: 32px 12px; color: #64748b; font-size: 13px;">
                            Select any element on the canvas to edit its Content, Style, and Advanced settings.
                        </div>
                    @endif
                @endif

                {{-- TAB 3: Page Settings & SEO --}}
                @if($leftTab === 'page_settings')
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Page Title</label>
                            <input type="text" wire:model.live="pageSettingsData.title" style="width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;" />
                        </div>
                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">URL Slug</label>
                            <input type="text" wire:model.live="pageSettingsData.slug" style="width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;" />
                        </div>
                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Layout</label>
                            <select wire:model.live="pageSettingsData.layout" style="width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;">
                                <option value="default">Default Theme Layout</option>
                                <option value="canvas">Elementor Canvas (No Header/Footer)</option>
                                <option value="full_width">Full Width</option>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">SEO Meta Title</label>
                            <input type="text" wire:model.live="pageSettingsData.meta_title" style="width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;" />
                        </div>
                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">SEO Meta Description</label>
                            <textarea rows="3" wire:model.live="pageSettingsData.meta_description" style="width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;"></textarea>
                        </div>
                    </div>
                @endif
            </div>
        </aside>

        {{-- Right Visual Canvas --}}
        <section
            style="flex: 1; padding: 24px; overflow-y: auto; display: flex; justify-content: center; align-items: flex-start;"
            x-on:dragover.prevent
            x-on:drop="handleDropOnContainer($event, null)"
        >
            <div
                class="fpb-canvas-root"
                style="max-width: {{ $viewportMaxWidth }}; width: 100%; min-height: 600px; background: #ffffff; border-radius: 8px; box-shadow: 0 4px 20px rgba(15, 23, 42, 0.08); padding: 20px; transition: max-width 0.2s ease;"
            >
                @if(empty($schema['elements']))
                    <div style="border: 2px dashed #cbd5e1; border-radius: 8px; padding: 64px 24px; text-align: center; color: #64748b;">
                        <div style="font-size: 15px; font-weight: 600; color: #334155; margin-bottom: 8px;">
                            Your Page Canvas is Empty
                        </div>
                        <div style="font-size: 13px; margin-bottom: 16px;">
                            Click or drag any widget from the left panel, or start with a Flex Container.
                        </div>
                        <button
                            type="button"
                            wire:click="addWidget('container')"
                            style="padding: 8px 16px; background: #2563eb; color: #ffffff; border: 0; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer;"
                        >
                            + Add Flex Container
                        </button>
                    </div>
                @else
                    @foreach($schema['elements'] as $element)
                        @include('filament-page-builder::livewire.partials.canvas-element', [
                            'element' => $element,
                            'activeElementId' => $activeElementId,
                            'targetContainerId' => $targetContainerId,
                        ])
                    @endforeach
                @endif
            </div>
        </section>
    </div>
</div>
