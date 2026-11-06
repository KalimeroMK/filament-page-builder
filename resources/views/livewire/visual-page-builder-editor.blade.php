@php
    $viewportMaxWidth = match ($viewport) {
        'tablet' => '1024px',
        'mobile' => '375px',
        default => '100%',
    };
    $activeIdSafe = $activeElement !== null
        ? (preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($activeElement['id'] ?? '')) ?: 'el')
        : null;
    $editorTabs = ['widgets' => '+ Blocks', 'inspector' => 'Edit', 'navigator' => 'Tree', 'templates' => 'Library'];
    if (! $isThemeTemplate) {
        $editorTabs['revisions'] = 'History';
    }
    $editorTabs['page_settings'] = $isThemeTemplate ? 'Settings' : 'SEO';
@endphp
<div
    class="fpb-visual-editor"
    x-data="{
        draggedElementId: null,
        draggedWidgetType: null,
        finderOpen: false,
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
        handleDropOnElement(event, targetId, parentContainerId, siblingIds) {
            event.preventDefault();
            event.stopPropagation();
            if (this.draggedElementId && this.draggedElementId !== targetId && Array.isArray(siblingIds)) {
                const fromIdx = siblingIds.indexOf(this.draggedElementId);
                const toIdx = siblingIds.indexOf(targetId);
                if (fromIdx !== -1 && toIdx !== -1) {
                    const updated = [...siblingIds];
                    const [moved] = updated.splice(fromIdx, 1);
                    updated.splice(toIdx, 0, moved);
                    $wire.reorderElements(updated, parentContainerId);
                }
                this.draggedElementId = null;
            }
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
    x-on:keydown.ctrl.k.window.prevent="finderOpen = true"
    x-on:keydown.meta.k.window.prevent="finderOpen = true"
>
    <style>{!! $generatedCss !!}</style>

    {{-- Finder (Ctrl+K) --}}
    <div
        x-show="finderOpen"
        x-cloak
        x-on:keydown.escape.window="finderOpen = false"
        style="position: fixed; inset: 0; z-index: 9999; background: rgba(15, 23, 42, 0.6); display: flex; align-items: flex-start; justify-content: center; padding-top: 12vh;"
        x-on:click.self="finderOpen = false"
    >
        <div style="background: #ffffff; width: min(92vw, 560px); border-radius: 12px; box-shadow: 0 25px 60px rgba(15,23,42,0.35); overflow: hidden;">
            <div style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0;">
                <input
                    type="text"
                    wire:model.live.debounce.250ms="finderQuery"
                    x-init="$nextTick(() => $el.focus())"
                    placeholder="Search elements by ID, type or text… (Ctrl+K)"
                    style="width: 100%; border: 0; outline: none; font-size: 15px; padding: 6px 0;"
                />
            </div>
            <div style="max-height: 50vh; overflow-y: auto;">
                @forelse($finderResults as $result)
                    <button
                        type="button"
                        wire:click="selectElementFromFinder('{{ preg_replace('/[^a-zA-Z0-9_-]/', '', $result['id']) }}'); finderOpen = false"
                        style="display: flex; align-items: center; justify-content: space-between; width: 100%; padding: 10px 16px; border: 0; border-bottom: 1px solid #f1f5f9; background: #ffffff; text-align: left; cursor: pointer;"
                    >
                        <span style="font-size: 13px; font-weight: 600; color: #0f172a;">{{ $result['label'] }}</span>
                        <span style="font-size: 11px; color: #64748b;">{{ $result['breadcrumb'] }}</span>
                    </button>
                @empty
                    <div style="padding: 20px; text-align: center; color: #64748b; font-size: 13px;">
                        {{ $finderQuery === '' ? 'Type to search elements…' : 'No elements found.' }}
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Top Editor Toolbar: Page Status + Undo/Redo + Viewport Switcher + Save Draft / Preview / Publish --}}
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

        {{-- Undo / Redo / Paste + Responsive Viewport Switcher --}}
        <div style="display: flex; align-items: center; gap: 10px;">
            <div style="display: flex; align-items: center; gap: 4px; background: #1e293b; padding: 4px; border-radius: 8px;">
                <button
                    type="button"
                    wire:click="undo"
                    @disabled(empty($historyStack))
                    title="Undo"
                    style="padding: 5px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; border: 0; cursor: {{ empty($historyStack) ? 'not-allowed' : 'pointer' }}; opacity: {{ empty($historyStack) ? '0.4' : '1' }}; background: transparent; color: #f8fafc;"
                >
                    ↶ Undo
                </button>
                <button
                    type="button"
                    wire:click="redo"
                    @disabled(empty($redoStack))
                    title="Redo"
                    style="padding: 5px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; border: 0; cursor: {{ empty($redoStack) ? 'not-allowed' : 'pointer' }}; opacity: {{ empty($redoStack) ? '0.4' : '1' }}; background: transparent; color: #f8fafc;"
                >
                    ↷ Redo
                </button>
                @if($clipboardElement !== null)
                    <button
                        type="button"
                        wire:click="pasteElement"
                        title="Paste Copied Block"
                        style="padding: 5px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; border: 0; cursor: pointer; background: #334155; color: #38bdf8;"
                    >
                        Paste Block
                    </button>
                @endif
            </div>

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

            @if($previewUrl !== null)
                <a
                    href="{{ $previewUrl }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    style="padding: 7px 14px; border-radius: 6px; font-size: 12px; font-weight: 600; background: #334155; color: #f8fafc; text-decoration: none; border: 1px solid #475569;"
                >
                    Preview
                </a>
            @endif

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
        <aside style="width: 360px; min-width: 360px; background: #ffffff; border-right: 1px solid #e2e8f0; display: flex; flex-direction: column;">
            {{-- Sidebar Mode Switcher --}}
            <div style="display: grid; grid-template-columns: repeat({{ count($editorTabs) }}, 1fr); border-bottom: 1px solid #e2e8f0; background: #f8fafc;">
                @foreach($editorTabs as $tabKey => $tabLabel)
                    <button
                        type="button"
                        wire:click="setLeftTab('{{ $tabKey }}')"
                        style="padding: 10px 2px; font-size: 11px; font-weight: 600; border: 0; border-bottom: 2px solid {{ $leftTab === $tabKey ? '#2563eb' : 'transparent' }}; background: {{ $leftTab === $tabKey ? '#ffffff' : 'transparent' }}; color: {{ $leftTab === $tabKey ? '#2563eb' : '#475569' }}; cursor: pointer;"
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
                                    @php $safeType = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $type) ?: 'widget'; @endphp
                                    <button
                                        type="button"
                                        draggable="true"
                                        x-on:dragstart="handleWidgetDragStart($event, '{{ $safeType }}')"
                                        wire:click="addWidget('{{ $safeType }}')"
                                        data-widget-type="{{ $safeType }}"
                                        style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px; padding: 12px 8px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; cursor: pointer; font-size: 12px; font-weight: 600; color: #1e293b;"
                                    >
                                        <span>{{ $widget::getLabel() }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                @endif

                {{-- TAB 2: Active Element Inspector --}}
                @if($leftTab === 'inspector')
                    @if($activeElement !== null && $activeIdSafe !== null)
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; padding-bottom: 10px; border-bottom: 1px solid #e2e8f0;">
                            <div>
                                <div style="font-size: 13px; font-weight: 700; color: #0f172a;">
                                    Editing: {{ ucfirst((string) ($activeElement['type'] ?? 'Element')) }}
                                </div>
                                <div style="font-size: 11px; color: #64748b;">ID: {{ $activeIdSafe }}</div>
                            </div>
                            <div style="display: flex; gap: 4px; flex-wrap: wrap;">
                                <button type="button" wire:click="moveElementUp('{{ $activeIdSafe }}')" title="Move Up" style="padding: 4px 7px; font-size: 11px; border: 1px solid #cbd5e1; border-radius: 4px; background: #f8fafc; cursor: pointer;">↑</button>
                                <button type="button" wire:click="moveElementDown('{{ $activeIdSafe }}')" title="Move Down" style="padding: 4px 7px; font-size: 11px; border: 1px solid #cbd5e1; border-radius: 4px; background: #f8fafc; cursor: pointer;">↓</button>
                                <button type="button" wire:click="copyElement('{{ $activeIdSafe }}')" title="Copy to Clipboard" style="padding: 4px 7px; font-size: 11px; border: 1px solid #cbd5e1; border-radius: 4px; background: #f8fafc; cursor: pointer;">Copy</button>
                                <button type="button" wire:click="duplicateElement('{{ $activeIdSafe }}')" title="Duplicate" style="padding: 4px 7px; font-size: 11px; border: 1px solid #cbd5e1; border-radius: 4px; background: #f8fafc; cursor: pointer;">Clone</button>
                                <button type="button" wire:click="removeElement('{{ $activeIdSafe }}')" title="Delete" style="padding: 4px 7px; font-size: 11px; border: 1px solid #fecaca; border-radius: 4px; background: #fef2f2; color: #dc2626; cursor: pointer;">Del</button>
                            </div>
                        </div>

                        <div wire:key="inspector-schema-{{ $activeIdSafe }}">
                            {{ $this->inspectorSchema }}
                        </div>

                        {{-- Dynamic Tag Bindings (v0.5.0) --}}
                        <div style="margin-top: 16px; padding-top: 12px; border-top: 1px solid #e2e8f0;">
                            <div style="font-size: 12px; font-weight: 700; margin-bottom: 8px; color: #0f172a;">
                                Dynamic Tags
                            </div>
                            <div style="font-size: 11px; color: #64748b; margin-bottom: 8px;">
                                Bind any setting to a dynamic data source (e.g. <code>model.attribute:title</code>, <code>page.title</code>).
                            </div>

                            @foreach(array_keys($activeElement['settings'] ?? []) as $settingKey)
                                @php
                                    $safeSettingKey = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $settingKey) ?? '';
                                    if ($safeSettingKey === '') { continue; }
                                    $currentBinding = $dynamicData[$safeSettingKey] ?? '';
                                @endphp
                                <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 6px;">
                                    <span style="font-size: 11px; color: #475569; min-width: 90px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $safeSettingKey }}">
                                        {{ $safeSettingKey }}
                                    </span>
                                    <input
                                        type="text"
                                        value="{{ $currentBinding }}"
                                        wire:change="updateDynamicBinding('{{ $safeSettingKey }}', $event.target.value)"
                                        placeholder="dynamic tag..."
                                        style="flex: 1; padding: 4px 8px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 11px; font-family: monospace;"
                                    />
                                    @if($currentBinding !== '')
                                        <button
                                            type="button"
                                            wire:click="clearDynamicBinding('{{ $safeSettingKey }}')"
                                            style="padding: 3px 6px; font-size: 10px; border: 1px solid #fecaca; background: #fef2f2; color: #dc2626; border-radius: 4px; cursor: pointer;"
                                        >×</button>
                                    @endif
                                </div>
                            @endforeach

                            <details style="margin-top: 8px;">
                                <summary style="font-size: 11px; font-weight: 600; color: #475569; cursor: pointer;">Available Dynamic Tags</summary>
                                <div style="margin-top: 6px;">
                                    @foreach($availableDynamicTags as $tagName => $tagInfo)
                                        <div style="font-size: 10px; color: #64748b; padding: 2px 0;">
                                            <code style="color: #2563eb;">{{ $tagName }}</code>
                                            <span>({{ $tagInfo['group'] }})</span>
                                        </div>
                                    @endforeach
                                </div>
                            </details>
                        </div>
                    @else
                        <div style="text-align: center; padding: 32px 12px; color: #64748b; font-size: 13px;">
                            Select any element on the canvas to edit its Content, Style, and Advanced settings.
                        </div>
                    @endif
                @endif

                {{-- TAB 2.5: Navigator (Element Tree View) --}}
                @if($leftTab === 'navigator')
                    <div>
                        <div style="font-size: 12px; font-weight: 700; margin-bottom: 10px; color: #0f172a;">
                            Navigator (Element Tree)
                        </div>
                        @if(empty($schema['elements']))
                            <div style="font-size: 12px; color: #64748b;">Canvas is empty. Add elements to inspect the tree.</div>
                        @else
                            @foreach($schema['elements'] as $treeEl)
                                @if(is_array($treeEl))
                                    @include('filament-page-builder::livewire.partials.navigator-tree-node', [
                                        'element' => $treeEl,
                                        'depth' => 0,
                                        'activeElementId' => $activeElementId,
                                    ])
                                @endif
                            @endforeach
                        @endif
                    </div>
                @endif

                {{-- TAB 3: Template Library + Import/Export --}}
                @if($leftTab === 'templates')
                    <div style="display: flex; flex-direction: column; gap: 16px;">
                        <div style="padding: 12px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;">
                            <div style="font-size: 12px; font-weight: 700; margin-bottom: 8px;">Save Current Layout as Template</div>
                            <input
                                type="text"
                                wire:model="newTemplateName"
                                placeholder="Template name (e.g. Hero Section)"
                                style="width: 100%; padding: 7px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 12px; margin-bottom: 6px;"
                            />
                            @error('newTemplateName')
                                <div style="color: #dc2626; font-size: 11px; margin-bottom: 6px;">{{ $message }}</div>
                            @enderror
                            <div style="display: flex; gap: 6px;">
                                <button
                                    type="button"
                                    wire:click="saveAsTemplate(false)"
                                    style="flex: 1; padding: 6px 10px; background: #2563eb; color: #fff; border: 0; border-radius: 6px; font-size: 11px; font-weight: 600; cursor: pointer;"
                                >
                                    Save Full Page
                                </button>
                                @if($activeElementId !== null)
                                    <button
                                        type="button"
                                        wire:click="saveAsTemplate(true)"
                                        style="flex: 1; padding: 6px 10px; background: #0f172a; color: #fff; border: 0; border-radius: 6px; font-size: 11px; font-weight: 600; cursor: pointer;"
                                    >
                                        Save Selected Block
                                    </button>
                                @endif
                            </div>
                        </div>

                        <div>
                            <div style="font-size: 12px; font-weight: 700; margin-bottom: 8px;">Saved Database Templates</div>
                            @forelse($savedTemplates as $tpl)
                                <div style="display: flex; align-items: center; justify-content: space-between; padding: 8px 10px; border: 1px solid #e2e8f0; border-radius: 6px; margin-bottom: 6px; font-size: 12px;">
                                    <div>
                                        <strong>{{ $tpl->name }}</strong>
                                        <span style="color: #64748b; font-size: 11px;">({{ $tpl->category }})</span>
                                    </div>
                                    <div style="display: flex; gap: 4px;">
                                        <button type="button" wire:click="applyDatabaseTemplate({{ (int) $tpl->id }}, true)" style="padding: 4px 8px; font-size: 11px; background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; border-radius: 4px; cursor: pointer;">+ Insert</button>
                                        <button type="button" wire:click="applyDatabaseTemplate({{ (int) $tpl->id }}, false)" style="padding: 4px 8px; font-size: 11px; background: #f8fafc; color: #334155; border: 1px solid #cbd5e1; border-radius: 4px; cursor: pointer;">Replace</button>
                                    </div>
                                </div>
                            @empty
                                <div style="font-size: 12px; color: #64748b;">No saved templates yet.</div>
                            @endforelse
                        </div>

                        @if(!empty($registeredTemplates))
                            <div>
                                <div style="font-size: 12px; font-weight: 700; margin-bottom: 8px;">Starter / Add-on Templates</div>
                                @foreach($registeredTemplates as $tplKey => $regTpl)
                                    @php $safeTplKey = preg_replace('/[^a-zA-Z0-9_.\-]/', '', (string) $tplKey); @endphp
                                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 8px 10px; border: 1px solid #e2e8f0; border-radius: 6px; margin-bottom: 6px; font-size: 12px;">
                                        <strong>{{ $regTpl['name'] }}</strong>
                                        <button type="button" wire:click="applyRegisteredTemplate('{{ $safeTplKey }}', true)" style="padding: 4px 8px; font-size: 11px; background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; border-radius: 4px; cursor: pointer;">+ Insert</button>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <div style="padding-top: 10px; border-top: 1px solid #e2e8f0;">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                                <span style="font-size: 12px; font-weight: 700;">Import / Export JSON Schema</span>
                                <button
                                    type="button"
                                    wire:click="exportCurrentSchemaJson"
                                    style="padding: 3px 8px; font-size: 11px; background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; border-radius: 4px; cursor: pointer;"
                                >
                                    Export Current JSON
                                </button>
                            </div>
                            <textarea
                                rows="4"
                                wire:model="importJsonPayload"
                                placeholder='Paste JSON schema here...'
                                style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 11px; font-family: monospace; margin-bottom: 6px;"
                            ></textarea>
                            @error('importJsonPayload')
                                <div style="color: #dc2626; font-size: 11px; margin-bottom: 6px;">{{ $message }}</div>
                            @enderror
                            <div style="display: flex; gap: 6px;">
                                <button type="button" wire:click="importSchemaFromJson(null, true)" style="flex: 1; padding: 6px 10px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 11px; font-weight: 600; cursor: pointer;">Append JSON</button>
                                <button type="button" wire:click="importSchemaFromJson(null, false)" style="flex: 1; padding: 6px 10px; background: #0f172a; color: #fff; border: 0; border-radius: 6px; font-size: 11px; font-weight: 600; cursor: pointer;">Replace Canvas</button>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- TAB 4: Revisions History --}}
                @if($leftTab === 'revisions')
                    <div>
                        <div style="font-size: 12px; font-weight: 700; margin-bottom: 10px;">Published Revisions</div>
                        @forelse($revisions as $rev)
                            <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px; border: 1px solid #e2e8f0; border-radius: 6px; margin-bottom: 8px; font-size: 12px;">
                                <div>
                                    <div><strong>v{{ $rev->version_number }}</strong> — {{ $rev->summary ?? 'Published snapshot' }}</div>
                                    <div style="font-size: 11px; color: #64748b;">{{ $rev->created_at?->format('Y-m-d H:i:s') }}</div>
                                </div>
                                <button
                                    type="button"
                                    wire:click="restoreRevisionInEditor({{ (int) $rev->id }})"
                                    style="padding: 5px 10px; font-size: 11px; font-weight: 600; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 4px; cursor: pointer;"
                                >
                                    Restore
                                </button>
                                <button
                                    type="button"
                                    wire:click="compareRevision({{ (int) $rev->id }})"
                                    style="padding: 5px 10px; font-size: 11px; font-weight: 600; background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; border-radius: 4px; cursor: pointer;"
                                >
                                    Compare
                                </button>
                            </div>
                        @empty
                            <div style="font-size: 12px; color: #64748b;">No published revisions yet. Click Publish to create v1.</div>
                        @endforelse
                    </div>

                    @if($revisionDiff !== null)
                        <div style="margin-top: 14px; padding: 12px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                                <strong style="font-size: 12px;">Diff vs Revision #{{ $compareRevisionId }}</strong>
                                <button type="button" wire:click="clearRevisionComparison" style="border: 0; background: transparent; color: #64748b; cursor: pointer; font-size: 11px;">Close</button>
                            </div>
                            <div style="font-size: 12px; margin-bottom: 8px;">
                                <span style="color: #16a34a;">+{{ $revisionDiff['added'] }} added</span> ·
                                <span style="color: #dc2626;">-{{ $revisionDiff['removed'] }} removed</span> ·
                                <span style="color: #d97706;">~{{ $revisionDiff['changed'] }} changed</span> ·
                                <span style="color: #64748b;">{{ $revisionDiff['unchanged'] }} unchanged</span>
                            </div>
                            @foreach($revisionDiff['by_type'] as $type => $counts)
                                <div style="font-size: 11px; color: #475569;">
                                    <strong>{{ $type }}</strong>:
                                    +{{ $counts['added'] }} / -{{ $counts['removed'] }} / ~{{ $counts['changed'] }}
                                </div>
                            @endforeach
                        </div>
                    @endif
                @endif

                {{-- TAB 5: Page Settings & SEO --}}
                @if($leftTab === 'page_settings')
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">{{ $isThemeTemplate ? 'Template Name' : 'Page Title' }}</label>
                            <input type="text" wire:model.live="pageSettingsData.title" style="width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;" />
                            @error('pageSettingsData.title')
                                <div style="color: #dc2626; font-size: 11px; margin-top: 4px;">{{ $message }}</div>
                            @enderror
                        </div>
                        @if($isThemeTemplate)
                            <div style="padding: 12px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 12px; color: #64748b;">
                                Region, priority and display conditions are available in the Advanced form.
                            </div>
                        @else
                            <div>
                                <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">URL Slug</label>
                                <input type="text" wire:model.live="pageSettingsData.slug" style="width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;" />
                                @error('pageSettingsData.slug')
                                    <div style="color: #dc2626; font-size: 11px; margin-top: 4px;">{{ $message }}</div>
                                @enderror
                            </div>
                            <div>
                                <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Layout</label>
                                <select wire:model.live="pageSettingsData.layout" style="width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;">
                                    @foreach($availableLayouts as $layoutKey)
                                        <option value="{{ $layoutKey }}">{{ ucfirst(str_replace('_', ' ', (string) $layoutKey)) }}</option>
                                    @endforeach
                                </select>
                                @error('pageSettingsData.layout')
                                    <div style="color: #dc2626; font-size: 11px; margin-top: 4px;">{{ $message }}</div>
                                @enderror
                            </div>
                            <div>
                                <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">SEO Meta Title</label>
                                <input type="text" wire:model.live="pageSettingsData.meta_title" style="width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;" />
                            </div>
                            <div>
                                <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">SEO Meta Description</label>
                                <textarea rows="3" wire:model.live="pageSettingsData.meta_description" style="width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;"></textarea>
                            </div>
                            <div>
                                <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">OpenGraph Image URL</label>
                                <input type="text" wire:model.live="pageSettingsData.og_image" style="width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;" />
                            </div>
                            <div>
                                <label style="display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 600; cursor: pointer;">
                                    <input type="checkbox" wire:model.live="pageSettingsData.noindex" />
                                    <span>Discourage search engines (noindex)</span>
                                </label>
                            </div>
                        @endif
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
                    @php
                        $rootSiblingIds = array_values(array_map(
                            fn (array $el): string => preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($el['id'] ?? '')) ?: 'el',
                            array_filter($schema['elements'], 'is_array')
                        ));
                    @endphp
                    @foreach($schema['elements'] as $element)
                        @include('filament-page-builder::livewire.partials.canvas-element', [
                            'element' => $element,
                            'parentContainerId' => null,
                            'siblingIds' => $rootSiblingIds,
                            'activeElementId' => $activeElementId,
                            'targetContainerId' => $targetContainerId,
                        ])
                    @endforeach
                @endif
            </div>
        </section>
    </div>
</div>
