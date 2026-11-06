<?php

declare(strict_types=1);

use KalimeroMK\FilamentPageBuilder\Models\Page;
use KalimeroMK\FilamentPageBuilder\Models\PageTemplate;
use KalimeroMK\FilamentPageBuilder\Models\Setting;

beforeEach(function (): void {
    config()->set('filament-page-builder.loop.allowed_models', [Page::class]);
});

it('renders Coming Soon template with generated CSS and Retry-After header', function (): void {
    $template = PageTemplate::create([
        'name' => 'Coming Soon Template',
        'schema' => [
            'version' => 1,
            'elements' => [
                ['id' => 'cs_h', 'type' => 'heading', 'settings' => ['text' => 'Наскоро доаѓаме!', 'color' => '#f59e0b']],
            ],
        ],
    ]);

    Setting::setGroup('maintenance', [
        'enabled' => true,
        'message' => 'На одржување сме.',
        'template_id' => $template->id,
        'retry_after_minutes' => 30,
    ]);

    $response = $this->get('/pages/anything');

    $response->assertStatus(503)
        ->assertSee('Наскоро доаѓаме!')
        ->assertHeader('Retry-After', '1800');

    expect($response->getContent())->toContain('--fpb-color: #f59e0b;');

    Setting::setGroup('maintenance', ['enabled' => false]);
});

it('applies Loop AJAX filters and returns total_pages metadata', function (): void {
    Page::create(['title' => 'Published A', 'slug' => 'pub-a', 'status' => 'published', 'draft_content' => ['version' => 1, 'elements' => []]]);
    Page::create(['title' => 'Published B', 'slug' => 'pub-b', 'status' => 'published', 'draft_content' => ['version' => 1, 'elements' => []]]);
    Page::create(['title' => 'Draft C', 'slug' => 'draft-c', 'status' => 'draft', 'draft_content' => ['version' => 1, 'elements' => []]]);

    $loopPage = Page::create([
        'title' => 'Filter Host',
        'slug' => 'filter-host',
        'draft_content' => [
            'version' => 1,
            'elements' => [
                [
                    'id' => 'loop_filter',
                    'type' => 'loop',
                    'settings' => [
                        'model_class' => Page::class,
                        'page_size' => 2,
                        'pagination' => 'page_numbers',
                        'ajax_filters' => ['status'],
                        'order_by' => 'id',
                        'order_direction' => 'asc',
                    ],
                ],
            ],
        ],
    ])->publish();

    $rendered = \KalimeroMK\FilamentPageBuilder\Facades\PageBuilder::renderPage($loopPage);
    expect($rendered->html)->toContain('fpb-loop-filters');

    expect(app(\KalimeroMK\FilamentPageBuilder\Services\LoopQueryBuilder::class)->total([
        'model_class' => Page::class,
        'filters' => [['field' => 'status', 'operator' => '=', 'value' => 'published']],
    ]))->toBe(3);

    $response = $this->getJson("/_builder/loop/{$loopPage->id}/loop_filter?page=1&filter_field=status&filter_value=published");
    $response->assertOk();

    expect($response->json('page'))->toBe(1)
        ->and($response->json('total'))->toBe(3)
        ->and($response->json('total_pages'))->toBe(2);

    expect($response->json('html'))->toContain('Published A')
        ->and($response->json('html'))->toContain('Published B')
        ->and($response->json('html'))->not->toContain('Draft C');

    $unfiltered = $this->getJson("/_builder/loop/{$loopPage->id}/loop_filter?page=1");
    $unfiltered->assertJson(['total' => 4, 'total_pages' => 2]);
});
