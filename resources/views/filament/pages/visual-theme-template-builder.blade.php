<x-filament-panels::page>
    @livewire('fpb-visual-editor', ['page' => $record, 'editorType' => 'theme_template'], key('fpb-visual-theme-template-' . $record->getKey()))
</x-filament-panels::page>
