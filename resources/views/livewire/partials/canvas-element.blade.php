@php
    $elId = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($element['id'] ?? '')) ?: 'el';
    $elType = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($element['type'] ?? '')) ?: 'widget';
    $parentSafeId = isset($parentContainerId) && $parentContainerId !== null
        ? (preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $parentContainerId) ?: null)
        : null;
    $isSelected = $activeElementId === $elId;
    $isTargetContainer = $targetContainerId === $elId;
    $registry = app(\KalimeroMK\FilamentPageBuilder\Registries\WidgetRegistry::class);
    $widget = $registry->get($elType);
    $isContainer = $widget !== null && $widget::isContainer();
    $settings = array_merge($widget ? $widget::getDefaultSettings() : [], is_array($element['settings'] ?? null) ? $element['settings'] : []);
    $editorLabel = trim((string) ($settings['admin_label'] ?? ''));
    $widgetLabel = $widget ? $widget::getLabel() : ucfirst($elType);
@endphp
<div
    wire:key="canvas-el-{{ $elId }}"
    draggable="true"
    x-on:dragstart="handleElementDragStart($event, '{{ $elId }}')"
    x-on:dragover.prevent
    x-on:drop="handleDropOnElement($event, '{{ $elId }}', @js($parentSafeId), @js($siblingIds ?? []))"
    wire:click.stop="selectElement('{{ $elId }}')"
    style="position: relative; outline: {{ $isSelected ? '2px solid #2563eb' : ($isContainer ? '1px dashed #94a3b8' : '1px solid transparent') }}; outline-offset: 2px; border-radius: 4px; margin-bottom: 8px; cursor: pointer; transition: outline-color 0.12s;"
>
    {{-- Element Floating Badge & Quick Actions --}}
    <div style="display: flex; align-items: center; justify-content: space-between; background: {{ $isSelected ? '#2563eb' : '#e2e8f0' }}; color: {{ $isSelected ? '#ffffff' : '#334155' }}; font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 4px 4px 0 0;">
        <span>{{ $editorLabel !== '' ? $editorLabel.' · '.$widgetLabel : $widgetLabel.' ('.$elId.')' }}</span>
        <span style="display: inline-flex; gap: 6px;">
            @if($isContainer)
                <button
                    type="button"
                    wire:click.stop="setTargetContainer('{{ $elId }}')"
                    style="background: transparent; border: 0; color: inherit; cursor: pointer; font-size: 10px; text-decoration: underline;"
                >
                    {{ $isTargetContainer ? '✓ Target' : '+ Target' }}
                </button>
            @endif
            <button type="button" wire:click.stop="copyElement('{{ $elId }}')" style="background: transparent; border: 0; color: inherit; cursor: pointer; font-size: 10px;">Copy</button>
            <button type="button" wire:click.stop="duplicateElement('{{ $elId }}')" style="background: transparent; border: 0; color: inherit; cursor: pointer; font-size: 10px;">Duplicate</button>
            <button type="button" wire:click.stop="removeElement('{{ $elId }}')" style="background: transparent; border: 0; color: inherit; cursor: pointer; font-size: 10px;">×</button>
        </span>
    </div>

    {{-- Element Content Preview --}}
    @if($isContainer)
        <div
            class="fpb-element fpb-container fpb-el-{{ $elId }}"
            style="min-height: 56px;"
            x-on:dragover.prevent
            x-on:drop="handleDropOnContainer($event, '{{ $elId }}')"
        >
            @if(!empty($element['children']) && is_array($element['children']))
                @php
                    $childSiblingIds = array_values(array_map(
                        fn (array $child): string => preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($child['id'] ?? '')) ?: 'el',
                        array_filter($element['children'], 'is_array')
                    ));
                @endphp
                @foreach($element['children'] as $child)
                    @include('filament-page-builder::livewire.partials.canvas-element', [
                        'element' => $child,
                        'parentContainerId' => $elId,
                        'siblingIds' => $childSiblingIds,
                        'activeElementId' => $activeElementId,
                        'targetContainerId' => $targetContainerId,
                    ])
                @endforeach
            @else
                <div style="padding: 16px; border: 1px dashed #cbd5e1; border-radius: 6px; text-align: center; color: #94a3b8; font-size: 12px;">
                    Drop or click widgets to insert inside this Flex Container
                </div>
            @endif
        </div>
    @elseif($widget !== null)
        <div style="pointer-events: none;">
            {!! $widget->render($element, $settings, '', ['is_preview' => true, 'is_editor' => true]) !!}
        </div>
    @endif
</div>
