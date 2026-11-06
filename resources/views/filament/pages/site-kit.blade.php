<x-filament-panels::page>
    <div style="display: flex; gap: 8px; margin-bottom: 16px;">
        <x-filament::button wire:click="exportKit" icon="heroicon-o-arrow-down-tray">
            Export Site Kit
        </x-filament::button>
        <x-filament::button wire:click="importKit" color="success" icon="heroicon-o-arrow-up-tray">
            Import Site Kit
        </x-filament::button>
    </div>

    {{ $this->form }}
</x-filament-panels::page>
