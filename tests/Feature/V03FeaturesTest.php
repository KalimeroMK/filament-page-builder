<?php

declare(strict_types=1);

use KalimeroMK\FilamentPageBuilder\Facades\PageBuilder;
use KalimeroMK\FilamentPageBuilder\Livewire\VisualPageBuilderEditor;
use KalimeroMK\FilamentPageBuilder\Models\Page;
use KalimeroMK\FilamentPageBuilder\Models\PageTemplate;
use Livewire\Livewire;

it('registers and renders all 6 v0.3 widgets (html, image_gallery, alert, accordion, tabs, testimonial) with valid Heroicons', function (): void {
    $registry = PageBuilder::widgets();

    foreach (['html', 'image_gallery', 'alert', 'accordion', 'tabs', 'testimonial'] as $type) {
        expect($registry->has($type))->toBeTrue();
        expect(svg($registry->get($type)::getIcon())->toHtml())->toContain('<svg');
    }

    $rendered = PageBuilder::renderSchema([
        'version' => 1,
        'elements' => [
            [
                'id' => 'html_1',
                'type' => 'html',
                'settings' => [
                    'html_content' => '<div class="safe-box">Safe HTML</div><script>alert(1)</script>',
                    'sanitize_html' => true,
                ],
            ],
            [
                'id' => 'gal_1',
                'type' => 'image_gallery',
                'settings' => [
                    'images' => [
                        ['src' => 'https://example.com/1.jpg', 'alt' => 'Slide 1', 'caption' => 'Прва слика'],
                        ['src' => 'https://example.com/2.jpg', 'alt' => 'Slide 2', 'caption' => 'Втора слика'],
                    ],
                    'columns' => 4,
                    'columns_mobile' => 1,
                ],
            ],
            [
                'id' => 'alt_1',
                'type' => 'alert',
                'settings' => [
                    'alert_type' => 'success',
                    'title' => 'Успешна акција',
                    'description' => 'Промените се зачувани.',
                ],
            ],
            [
                'id' => 'acc_1',
                'type' => 'accordion',
                'settings' => [
                    'items' => [
                        ['title' => 'Прашање 1', 'content' => 'Одговор 1'],
                    ],
                ],
            ],
            [
                'id' => 'tab_1',
                'type' => 'tabs',
                'settings' => [
                    'tabs' => [
                        ['title' => 'Таб А', 'content' => 'Содржина А'],
                    ],
                ],
            ],
            [
                'id' => 'tst_1',
                'type' => 'testimonial',
                'settings' => [
                    'quote' => 'Одличен плагин за Laravel!',
                    'author_name' => 'Зоран',
                    'author_role' => 'Founder',
                    'rating' => 5,
                ],
            ],
        ],
    ]);

    expect($rendered->html)
        ->toContain('Safe HTML')
        ->not->toContain('<script>')
        ->toContain('Прва слика')
        ->toContain('Втора слика')
        ->toContain('https://example.com/2.jpg')
        ->toContain('Успешна акција')
        ->toContain('Прашање 1')
        ->toContain('Таб А')
        ->toContain('Одличен плагин за Laravel!')
        ->toContain('★★★★★');

    expect($rendered->css)
        ->toContain('--fpb-gallery-cols: 4;')
        ->toContain('@container fpb-canvas (max-width: 767px) { .fpb-el-gal_1 { --fpb-gallery-cols: 1; } }');
});

it('supports Undo/Redo, Copy/Paste, Template Library (Save/Insert/Import/Export), and Revision Restore in VisualPageBuilderEditor', function (): void {
    $page = Page::create([
        'title' => 'Тест v0.3',
        'slug' => 'test-v03',
    ]);

    $editor = Livewire::test(VisualPageBuilderEditor::class, ['page' => $page]);

    // 1. Add a Heading and test Undo / Redo
    $editor->call('addWidget', 'heading')
        ->call('updateActiveElementSettings', ['text' => 'Прв Наслов']);

    expect(count($editor->get('schema')['elements']))->toBe(1)
        ->and($editor->get('schema')['elements'][0]['settings']['text'])->toBe('Прв Наслов');

    // Undo the text edit
    $editor->call('undo');
    expect($editor->get('schema')['elements'][0]['settings']['text'])->toBe('Add Your Heading Text Here');

    // Undo adding the widget
    $editor->call('undo');
    expect($editor->get('schema')['elements'])->toBeEmpty();

    // Redo adding the widget & Redo text edit
    $editor->call('redo')->call('redo');
    $headingId = $editor->get('schema')['elements'][0]['id'];
    expect($editor->get('schema')['elements'][0]['settings']['text'])->toBe('Прв Наслов');

    // 2. Copy & Paste block
    $editor->call('copyElement', $headingId)
        ->call('pasteElement');

    expect(count($editor->get('schema')['elements']))->toBe(2)
        ->and($editor->get('schema')['elements'][1]['id'])->not->toBe($headingId)
        ->and($editor->get('schema')['elements'][1]['settings']['text'])->toBe('Прв Наслов');

    // 3. Save as Template to Database and apply it
    $editor->set('newTemplateName', 'Мој Hero Темплејт')
        ->set('newTemplateCategory', 'hero')
        ->call('saveAsTemplate', false);

    $template = PageTemplate::query()->where('name', 'Мој Hero Темплејт')->first();
    expect($template)->not->toBeNull()
        ->and(count($template->schema['elements']))->toBe(2);

    // Test JSON Export & Import on PageTemplate
    $exportedJson = $template->exportToJson();
    $importedTemplate = PageTemplate::importFromJson($exportedJson);
    expect($importedTemplate->name)->toBe('Мој Hero Темплејт')
        ->and(count($importedTemplate->schema['elements']))->toBe(2);

    // Reject arbitrary JSON without 'elements' so it never wipes the canvas
    expect(fn () => PageTemplate::importFromJson('{"foo":"bar"}'))->toThrow(InvalidArgumentException::class);

    $editor->set('importJsonPayload', '{"foo":"bar"}')
        ->call('importSchemaFromJson', null, false)
        ->assertHasErrors(['importJsonPayload']);
    expect(count($editor->get('schema')['elements']))->toBe(2);

    // Export current schema JSON populates importJsonPayload
    $editor->call('exportCurrentSchemaJson');
    expect($editor->get('importJsonPayload'))->toContain('Прв Наслов');

    // Apply database template in editor (append = true -> 2 + 2 = 4 elements)
    $editor->call('applyDatabaseTemplate', $template->id, true);
    expect(count($editor->get('schema')['elements']))->toBe(4);

    // 4. Publish v1, then edit & restore Revision #1 from inside the visual editor
    $editor->call('publishPage');
    $v1RevisionId = $page->revisions()->firstOrFail()->id;

    $editor->call('removeElement', $headingId);
    expect(count($editor->get('schema')['elements']))->toBe(3);

    $editor->call('restoreRevisionInEditor', $v1RevisionId);
    expect(count($editor->get('schema')['elements']))->toBe(4);
});
