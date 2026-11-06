<?php

declare(strict_types=1);

use KalimeroMK\FilamentPageBuilder\Models\Page;
use KalimeroMK\FilamentPageBuilder\Models\Setting;

beforeEach(function (): void {
    config()->set('filament-page-builder.loop.allowed_models', [Page::class]);
});

it('blocks builder endpoints during maintenance but allows preview and livewire', function (): void {
    Setting::setGroup('maintenance', ['enabled' => true]);

    $this->get('/_builder/loop/1/loop_1')->assertStatus(503);

    $page = Page::create(['title' => 'Preview Me', 'slug' => 'preview-me', 'draft_content' => ['version' => 1, 'elements' => []]]);
    $this->get('/_builder/preview/' . $page->id)->assertStatus(403);

    $this->get('/livewire/update')->assertNotFound();

    Setting::setGroup('maintenance', ['enabled' => false]);
});

it('rejects unexposed or invalid AJAX filters with 422', function (): void {
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
                        'ajax_filters' => ['status'],
                    ],
                ],
            ],
        ],
    ])->publish();

    // Unexposed field is rejected
    $this->getJson("/_builder/loop/{$loopPage->id}/loop_filter?page=1&filter_field=password&filter_value=secret")
        ->assertStatus(422);

    // Exposed field works
    $this->getJson("/_builder/loop/{$loopPage->id}/loop_filter?page=1&filter_field=status&filter_value=published")
        ->assertOk();
});
