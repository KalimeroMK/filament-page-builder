<?php

declare(strict_types=1);

use KalimeroMK\FilamentPageBuilder\Enums\ThemeRegionType;
use KalimeroMK\FilamentPageBuilder\Facades\PageBuilder;
use KalimeroMK\FilamentPageBuilder\Livewire\VisualPageBuilderEditor;
use KalimeroMK\FilamentPageBuilder\Models\Menu;
use KalimeroMK\FilamentPageBuilder\Models\Page;
use KalimeroMK\FilamentPageBuilder\Models\PageTemplate;
use KalimeroMK\FilamentPageBuilder\Models\Popup;
use KalimeroMK\FilamentPageBuilder\Models\Setting;
use KalimeroMK\FilamentPageBuilder\Models\ThemeTemplate;
use KalimeroMK\FilamentPageBuilder\Services\SiteKitService;
use Livewire\Livewire;

beforeEach(function (): void {
    config()->set('filament-page-builder.loop.allowed_models', [Page::class]);
});

it('exports and imports a full site kit with fresh element IDs and draft items', function (): void {
    Page::create([
        'title' => 'Kit Home',
        'slug' => 'kit-home',
        'draft_content' => [
            'version' => 1,
            'elements' => [
                ['id' => 'kit_h', 'type' => 'heading', 'settings' => ['text' => 'Kit Title']],
            ],
        ],
    ])->publish();

    ThemeTemplate::create([
        'title' => 'Kit Header',
        'region' => ThemeRegionType::Header,
        'conditions' => [['type' => 'include', 'target' => 'entire_site', 'value' => '*']],
        'draft_content' => ['version' => 1, 'elements' => []],
    ])->publish();

    PageTemplate::create([
        'name' => 'Kit Template',
        'schema' => ['version' => 1, 'elements' => []],
    ]);

    Popup::create([
        'title' => 'Kit Popup',
        'draft_content' => ['version' => 1, 'elements' => []],
    ])->publish();

    Menu::create(['title' => 'Kit Menu', 'slug' => 'kit-menu', 'items' => [['label' => 'Home', 'url' => '/']]]);

    $service = app(SiteKitService::class);
    $kit = $service->exportKit();

    expect($kit['kit'])->toBe('filament-page-builder-site-kit')
        ->and($kit['pages'])->toHaveCount(1)
        ->and($kit['theme_templates'])->toHaveCount(1)
        ->and($kit['templates'])->toHaveCount(1)
        ->and($kit['popups'])->toHaveCount(1)
        ->and($kit['menus'])->toHaveCount(1);

    $originalElementId = $kit['pages'][0]['schema']['elements'][0]['id'];
    expect($originalElementId)->toBe('kit_h');

    $counts = $service->importKit($kit);

    expect($counts['pages'])->toBe(1)
        ->and($counts['theme_templates'])->toBe(1)
        ->and($counts['templates'])->toBe(1)
        ->and($counts['popups'])->toBe(1)
        ->and($counts['menus'])->toBe(1);

    expect(Page::count())->toBe(2);

    $importedPage = Page::query()->latest('id')->firstOrFail();
    expect($importedPage->getWorkingSchema()['elements'][0]['id'])->not->toBe('kit_h')
        ->and($importedPage->getWorkingSchema()['elements'][0]['settings']['text'])->toBe('Kit Title');

    expect(fn () => $service->importKit(['kit' => 'wrong', 'pages' => []]))->toThrow(InvalidArgumentException::class);
});

it('supports Loop Carousel layout and AJAX Load More pagination', function (): void {
    foreach (range(1, 8) as $i) {
        Page::create(['title' => "Loop {$i}", 'slug' => "loop-{$i}", 'draft_content' => ['version' => 1, 'elements' => []]]);
    }

    $loopPage = Page::create([
        'title' => 'Loop Host',
        'slug' => 'loop-host',
        'draft_content' => [
            'version' => 1,
            'elements' => [
                [
                    'id' => 'loop_ajax',
                    'type' => 'loop',
                    'settings' => [
                        'model_class' => Page::class,
                        'layout' => 'carousel',
                        'pagination' => 'load_more',
                        'page_size' => 3,
                        'order_by' => 'id',
                        'order_direction' => 'asc',
                    ],
                ],
            ],
        ],
    ])->publish();

    $rendered = PageBuilder::renderPage($loopPage);
    expect($rendered->html)->toContain('fpb-loop--carousel')
        ->and($rendered->html)->toContain('Load More');

    $response = $this->getJson("/_builder/loop/{$loopPage->id}/loop_ajax?page=2");
    $response->assertOk()
        ->assertJson(['page' => 2, 'has_more' => true]);

    expect($response->json('html'))->toContain('Loop 4');

    $lastPage = $this->getJson("/_builder/loop/{$loopPage->id}/loop_ajax?page=3");
    expect($lastPage->json('has_more'))->toBeFalse();
});

it('returns 503 maintenance page when enabled but lets admin and preview through', function (): void {
    Setting::setGroup('maintenance', ['enabled' => true, 'message' => 'На одржување сме.']);

    $this->get('/pages/anything')->assertStatus(503)
        ->assertSee('На одржување сме.');

    \Illuminate\Support\Facades\Route::get('/admin/login', fn (): string => 'admin-ok');
    $this->get('/admin/login')->assertOk();

    $page = Page::create(['title' => 'Preview Me', 'slug' => 'preview-me', 'draft_content' => ['version' => 1, 'elements' => []]]);
    $this->get($page->getPreviewUrl())->assertOk();

    Setting::setGroup('maintenance', ['enabled' => false]);
});

it('finds elements via the Finder (Ctrl+K) search in the visual editor', function (): void {
    $page = Page::create([
        'title' => 'Finder Page',
        'slug' => 'finder-page',
        'draft_content' => [
            'version' => 1,
            'elements' => [
                ['id' => 'hero_h', 'type' => 'heading', 'settings' => ['text' => 'Уникатен Наслов']],
            ],
        ],
    ]);

    $editor = Livewire::test(VisualPageBuilderEditor::class, ['page' => $page]);

    $editor->set('finderQuery', 'Уникатен');
    $results = $editor->instance()->finderResults;

    expect($results)->toHaveCount(1)
        ->and($results[0]['id'])->toBe('hero_h');

    $editor->set('finderQuery', 'heading');
    expect($editor->instance()->finderResults)->toHaveCount(1);
});


it('remaps template references when importing a site kit', function (): void {
    $template = PageTemplate::create([
        'name' => 'Remap Template',
        'schema' => ['version' => 1, 'elements' => []],
    ]);

    $loopPage = Page::create([
        'title' => 'Loop Kit',
        'slug' => 'loop-kit',
        'draft_content' => [
            'version' => 1,
            'elements' => [
                [
                    'id' => 'loop_ref',
                    'type' => 'loop',
                    'settings' => ['model_class' => Page::class, 'template_id' => $template->id],
                ],
            ],
        ],
    ]);

    $kit = app(SiteKitService::class)->exportKit();
    $counts = app(SiteKitService::class)->importKit($kit);

    expect($counts['templates'])->toBe(1);

    $importedTemplate = PageTemplate::query()->latest('id')->firstOrFail();
    $importedPage = Page::query()->where('slug', '!=', 'loop-kit')->latest('id')->firstOrFail();

    expect((int) $importedPage->getWorkingSchema()['elements'][0]['settings']['template_id'])
        ->toBe((int) $importedTemplate->id)
        ->not->toBe((int) $template->id);
});

it('reports hasMore correctly when page_size equals limit', function (): void {
    foreach (range(1, 7) as $i) {
        Page::create(['title' => "Boundary {$i}", 'slug' => "boundary-{$i}", 'draft_content' => ['version' => 1, 'elements' => []]]);
    }

    $builder = app(\KalimeroMK\FilamentPageBuilder\Services\LoopQueryBuilder::class);

    $settings = [
        'model_class' => Page::class,
        'page_size' => 6,
        'limit' => 6,
        'page' => 1,
        'order_by' => 'id',
        'order_direction' => 'asc',
    ];

    expect($builder->hasMore($settings))->toBeTrue();

    $settings['page'] = 2;
    expect($builder->hasMore($settings))->toBeFalse();

    $settings['page_size'] = 100;
    $settings['page'] = 1;
    expect($builder->hasMore($settings))->toBeTrue();

    $settings['page'] = 2;
    expect($builder->hasMore($settings))->toBeFalse();
});
