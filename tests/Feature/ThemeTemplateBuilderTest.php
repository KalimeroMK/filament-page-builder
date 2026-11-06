<?php

declare(strict_types=1);

use KalimeroMK\FilamentPageBuilder\Enums\PageStatus;
use KalimeroMK\FilamentPageBuilder\Enums\ThemeRegionType;
use KalimeroMK\FilamentPageBuilder\Filament\Resources\ThemeTemplateResource;
use KalimeroMK\FilamentPageBuilder\Livewire\VisualPageBuilderEditor;
use KalimeroMK\FilamentPageBuilder\Models\ThemeTemplate;
use Livewire\Livewire;

it('builds, saves and publishes a theme template in the visual editor', function (): void {
    $template = ThemeTemplate::create([
        'title' => 'Visual Header',
        'region' => ThemeRegionType::Header,
        'draft_content' => ['version' => 1, 'elements' => []],
    ]);

    $editor = Livewire::test(VisualPageBuilderEditor::class, ['page' => $template])
        ->assertSet('editorType', 'theme_template')
        ->call('addWidget', 'heading')
        ->call('updateActiveElementSettings', ['text' => 'Header from Visual Builder']);

    $editor->call('saveDraft');
    $template->refresh();
    expect($template->getWorkingSchema()['elements'][0]['settings']['text'])->toBe('Header from Visual Builder')
        ->and($template->content)->toBeNull();

    $editor->call('publishPage');
    $template->refresh();
    expect($template->status)->toBe(PageStatus::Published)
        ->and($template->getPublishedSchema()['elements'][0]['settings']['text'])->toBe('Header from Visual Builder');

    $editor->call('setLeftTab', 'revisions')
        ->assertSet('leftTab', 'inspector');
});

it('registers the theme template visual builder route', function (): void {
    expect(ThemeTemplateResource::getPages())->toHaveKey('builder');
});
