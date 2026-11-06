@php
    $elId = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($element['id'] ?? '')) ?: 'el';
    $elType = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($element['type'] ?? '')) ?: 'widget';
    $registry ??= app(\KalimeroMK\FilamentPageBuilder\Registries\WidgetRegistry::class);
    $widget = $registry->get($elType);
    $depthInt = max(0, (int) ($depth ?? 0));
    $indentPx = 8 + (min($depthInt, 8) * 14);
    $isContainer = $widget !== null && $widget::isContainer();
    $hasChildren = $isContainer && $depthInt < 20 && ! empty($element['children']) && is_array($element['children']);
    $isSelected = $activeElementId === $elId;
    $isHidden = ! empty($element['settings']['hide_element']);
@endphp
<div wire:key="nav-node-{{ $depthInt }}-{{ $elId }}" x-data="{ expanded: true }" style="margin-bottom: 4px;">
    <div
        wire:click="selectElement('{{ $elId }}')"
        style="display: flex; align-items: center; justify-content: space-between; padding: 6px 8px; padding-left: {{ $indentPx }}px; border-radius: 6px; border: 1px solid {{ $isSelected ? '#2563eb' : '#e2e8f0' }}; background: {{ $isSelected ? '#eff6ff' : '#f8fafc' }}; color: {{ $isHidden ? '#94a3b8' : '#1e293b' }}; font-size: 12px; cursor: pointer;"
    >
        <div style="display: flex; align-items: center; gap: 6px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
            @if($isContainer)
                <button
                    type="button"
                    x-on:click.stop="expanded = !expanded"
                    style="border: 0; background: transparent; padding: 0 2px; cursor: pointer; font-size: 11px; color: #64748b;"
                >
                    <span x-text="expanded ? '▾' : '▸'">▾</span>
                </button>
            @else
                <span style="display: inline-block; width: 12px; color: #94a3b8;">•</span>
            @endif
            <span style="font-weight: 600; {{ $isHidden ? 'text-decoration: line-through;' : '' }}">
                {{ $widget ? $widget::getLabel() : ucfirst($elType) }}
            </span>
            <span style="font-size: 10px; color: #64748b;">#{{ $elId }}</span>
        </div>

        <div style="display: inline-flex; align-items: center; gap: 4px;">
            <button
                type="button"
                wire:click.stop="toggleElementVisibility('{{ $elId }}')"
                title="{{ $isHidden ? 'Show Element' : 'Hide Element' }}"
                style="border: 0; background: transparent; cursor: pointer; font-size: 11px; color: {{ $isHidden ? '#ef4444' : '#64748b' }};"
            >
                {{ $isHidden ? 'Hidden' : 'Visible' }}
            </button>
            <button
                type="button"
                wire:click.stop="moveElementUp('{{ $elId }}')"
                title="Move Up"
                style="border: 0; background: transparent; cursor: pointer; font-size: 11px; color: #64748b;"
            >↑</button>
            <button
                type="button"
                wire:click.stop="moveElementDown('{{ $elId }}')"
                title="Move Down"
                style="border: 0; background: transparent; cursor: pointer; font-size: 11px; color: #64748b;"
            >↓</button>
            <button
                type="button"
                wire:click.stop="removeElement('{{ $elId }}')"
                title="Delete"
                style="border: 0; background: transparent; cursor: pointer; font-size: 11px; color: #dc2626;"
            >×</button>
        </div>
    </div>

    @if($hasChildren)
        <div x-show="expanded" style="margin-top: 4px;">
            @foreach($element['children'] as $child)
                @if(is_array($child))
                    @include('filament-page-builder::livewire.partials.navigator-tree-node', [
                        'element' => $child,
                        'depth' => $depthInt + 1,
                        'activeElementId' => $activeElementId,
                        'registry' => $registry,
                    ])
                @endif
            @endforeach
        </div>
    @endif
</div>
