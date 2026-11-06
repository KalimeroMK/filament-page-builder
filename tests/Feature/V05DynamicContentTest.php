<?php

declare(strict_types=1);

use KalimeroMK\FilamentPageBuilder\Facades\PageBuilder;
use KalimeroMK\FilamentPageBuilder\Livewire\VisualPageBuilderEditor;
use KalimeroMK\FilamentPageBuilder\Models\Page;
use KalimeroMK\FilamentPageBuilder\Models\PageRevision;
use KalimeroMK\FilamentPageBuilder\Models\PageTemplate;
use KalimeroMK\FilamentPageBuilder\Services\LoopQueryBuilder;
use Livewire\Livewire;

beforeEach(function (): void {
    config()->set('filament-page-builder.loop.allowed_models', [Page::class, PageRevision::class]);
});

it('queries Eloquent records via LoopQueryBuilder with filters, limit and ordering', function (): void {
    Page::create(['title' => 'Прва', 'slug' => 'first', 'status' => 'published', 'content' => ['version' => 1, 'elements' => []]]);
    Page::create(['title' => 'Втора', 'slug' => 'second', 'status' => 'published', 'content' => ['version' => 1, 'elements' => []]]);
    Page::create(['title' => 'Трета', 'slug' => 'third', 'status' => 'draft']);

    $builder = app(LoopQueryBuilder::class);

    $records = $builder->query([
        'model_class' => Page::class,
        'limit' => 10,
        'order_by' => 'id',
        'order_direction' => 'asc',
    ]);
    expect($records->count())->toBe(3);

    $filtered = $builder->query([
        'model_class' => Page::class,
        'limit' => 5,
        'order_by' => 'id',
        'order_direction' => 'asc',
        'filters' => [
            ['field' => 'status', 'operator' => '=', 'value' => 'published'],
        ],
    ]);
    expect($filtered->count())->toBe(2)
        ->and($filtered->first()->title)->toBe('Прва');

    $limited = $builder->query([
        'model_class' => Page::class,
        'limit' => 1,
        'order_by' => 'id',
        'order_direction' => 'desc',
    ]);
    expect($limited->count())->toBe(1)
        ->and($limited->first()->title)->toBe('Трета');

    // Invalid model class returns empty collection
    expect($builder->query(['model_class' => 'App\\Models\\DoesNotExist'])->count())->toBe(0);
});

it('denies loop queries for models not in the allowlist (fail closed)', function (): void {
    config()->set('filament-page-builder.loop.allowed_models', []);

    $records = app(LoopQueryBuilder::class)->query([
        'model_class' => Page::class,
        'limit' => 5,
    ]);

    expect($records->count())->toBe(0);
});

it('renders LoopWidget with per-record dynamic tags, unique element IDs, and item CSS', function (): void {
    Page::create(['title' => 'Запис А', 'slug' => 'rec-a']);
    Page::create(['title' => 'Запис Б', 'slug' => 'rec-b']);

    $itemSchema = [
        'version' => 1,
        'elements' => [
            [
                'id' => 'loop_heading',
                'type' => 'heading',
                'settings' => ['text' => '', 'tag' => 'h3', 'font_size' => 22],
                'dynamic' => ['text' => 'model.attribute:title'],
            ],
        ],
    ];

    $rendered = PageBuilder::renderSchema([
        'version' => 1,
        'elements' => [
            [
                'id' => 'loop_1',
                'type' => 'loop',
                'settings' => [
                    'model_class' => Page::class,
                    'limit' => 5,
                    'order_by' => 'id',
                    'order_direction' => 'asc',
                    'layout' => 'grid',
                    'columns' => 2,
                    'item_schema_json' => json_encode($itemSchema),
                ],
            ],
        ],
    ]);

    expect($rendered->html)
        ->toContain('Запис А')
        ->toContain('Запис Б')
        ->toContain('fpb-loop--grid')
        ->toContain('fpb-loop-item-styles')
        ->toContain('--fpb-font-size: 22px;')
        ->not->toContain('loop_heading"');

    expect($rendered->css)->toContain('--fpb-loop-cols: 2;');
});

it('shows config error for malformed inline JSON and blocks circular template references', function (): void {
    Page::create(['title' => 'Тест', 'slug' => 'test']);

    $malformed = PageBuilder::renderSchema([
        'version' => 1,
        'elements' => [
            [
                'id' => 'loop_bad',
                'type' => 'loop',
                'settings' => [
                    'model_class' => Page::class,
                    'item_schema_json' => '{"foo":"bar"}',
                ],
            ],
        ],
    ]);

    expect($malformed->html)->toContain('Invalid item schema JSON');

    $template = PageTemplate::create([
        'name' => 'Self Loop',
        'schema' => [
            'version' => 1,
            'elements' => [
                [
                    'id' => 'self_loop',
                    'type' => 'loop',
                    'settings' => [
                        'model_class' => Page::class,
                        'template_id' => 1,
                    ],
                ],
            ],
        ],
    ]);
    $template->update(['schema' => array_replace_recursive($template->schema, [
        'elements' => [
            ['settings' => ['template_id' => $template->id]],
        ],
    ])]);

    $cyclic = PageBuilder::renderSchema([
        'version' => 1,
        'elements' => [
            [
                'id' => 'loop_cyc',
                'type' => 'loop',
                'settings' => [
                    'model_class' => Page::class,
                    'template_id' => $template->id,
                ],
            ],
        ],
    ]);

    expect($cyclic->html)->toContain('circular template reference');
});

it('resolves dynamic.model relations and clears stale record context on null relations', function (): void {
    $page = Page::create(['title' => 'Родител Страница', 'slug' => 'parent-page']);
    $revision = $page->revisions()->create([
        'version_number' => 1,
        'schema_snapshot' => ['version' => 1, 'elements' => []],
    ]);

    $schema = [
        'version' => 1,
        'elements' => [
            [
                'id' => 'dyn_rel',
                'type' => 'heading',
                'settings' => ['text' => 'Fallback'],
                'dynamic' => [
                    'model' => 'page',
                    'text' => 'model.attribute:title',
                ],
            ],
        ],
    ];

    $rendered = PageBuilder::renderSchema($schema, ['record' => $revision]);
    expect($rendered->html)->toContain('Родител Страница');

    // Null relation: revision without page -> record context cleared, fallback used
    $orphanRevision = new PageRevision();
    $renderedNull = PageBuilder::renderSchema($schema, ['record' => $orphanRevision]);
    expect($renderedNull->html)->toContain('Fallback')
        ->not->toContain('Родител Страница');
});

it('binds dynamic tags to widget settings in VisualPageBuilderEditor', function (): void {
    $page = Page::create(['title' => 'Dynamic Test', 'slug' => 'dyn-test']);

    $editor = Livewire::test(VisualPageBuilderEditor::class, ['page' => $page]);

    $editor->call('addWidget', 'heading');

    $editor->call('updateDynamicBinding', 'text', 'model.attribute:title');
    expect($editor->get('schema')['elements'][0]['dynamic']['text'])->toBe('model.attribute:title');

    $editor->call('clearDynamicBinding', 'text');
    expect($editor->get('schema')['elements'][0]['dynamic'])->not->toHaveKey('text');
});
