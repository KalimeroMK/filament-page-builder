<?php

declare(strict_types=1);

use KalimeroMK\FilamentPageBuilder\Filament\Resources\MenuResource;
use KalimeroMK\FilamentPageBuilder\Livewire\MenuBuilder;
use KalimeroMK\FilamentPageBuilder\Models\Menu;
use KalimeroMK\FilamentPageBuilder\Models\Page;
use Livewire\Livewire;

it('edits menu items, picks pages, reorders nested items and saves a clean hierarchy', function (): void {
    Page::create(['title' => 'Post Fullwidth', 'slug' => 'post-fullwidth']);

    $menu = Menu::create([
        'title' => 'Main Menu',
        'slug' => 'main',
        'items' => [
            ['label' => 'Home', 'url' => '/pages/home'],
            ['label' => 'News', 'url' => '/pages/news', 'children' => [
                ['label' => 'Tutorials', 'url' => '/pages/blog'],
            ]],
        ],
    ]);

    $editor = Livewire::test(MenuBuilder::class, ['menu' => $menu])
        ->assertCount('items', 2)
        ->assertCount('expandedKeys', 0)
        ->call('addItem');

    $rootKey = $editor->get('items')[2]['_key'];
    $editor->call('setLinkType', $rootKey, 'page')
        ->call('selectPage', $rootKey, 'post-fullwidth')
        ->assertSet('items.2.url', '/pages/post-fullwidth')
        ->assertSet('items.2.label', 'Post Fullwidth')
        ->call('addChild', 2);

    $childKey = $editor->get('items')[2]['children'][0]['_key'];
    $editor->call('setLinkType', $childKey, 'page', 2)
        ->call('selectPage', $childKey, 'post-fullwidth', 2)
        ->call('moveItem', $childKey, 2, $editor->get('items')[0]['_key'], null);

    $reordered = $editor->get('items');
    expect($reordered[0]['_key'])->toBe($childKey)
        ->and($reordered[0]['label'])->toBe('Post Fullwidth')
        ->and($reordered[0]['children'])->toBeEmpty()
        ->and($reordered[3]['children'])->toBeEmpty();

    $editor->set('titleData.title', 'Updated Main Menu')
        ->set('titleData.slug', 'updated-main')
        ->call('save');

    $menu->refresh();
    expect($menu->title)->toBe('Updated Main Menu')
        ->and($menu->slug)->toBe('updated-main')
        ->and($menu->items[0]['label'])->toBe('Post Fullwidth')
        ->and($menu->items[0]['url'])->toBe('/pages/post-fullwidth')
        ->and($menu->items[0])->not->toHaveKeys(['_key', 'link_type', 'page_slug']);
});

it('adds selected pages from the sidebar and adds custom links', function (): void {
    Page::create(['title' => 'News', 'slug' => 'news']);
    Page::create(['title' => 'Documentation', 'slug' => 'docs']);
    $menu = Menu::create(['title' => 'Sidebar Menu', 'slug' => 'sidebar']);

    $editor = Livewire::test(MenuBuilder::class, ['menu' => $menu])
        ->call('setPanelTab', 'all')
        ->call('toggleSelectAll', true)
        ->assertSet('selectedPages', ['docs', 'news'])
        ->call('addSelectedPages')
        ->assertCount('items', 2)
        ->set('customLink.label', 'External Guide')
        ->set('customLink.url', 'https://example.com/guide')
        ->call('addCustomLink')
        ->assertCount('items', 3)
        ->call('save');

    $menu->refresh();
    expect($menu->items)->toHaveCount(3)
        ->and($menu->items[0]['url'])->toBe('/pages/docs')
        ->and($menu->items[1]['url'])->toBe('/pages/news')
        ->and($menu->items[2]['url'])->toBe('https://example.com/guide');

});

it('registers the visual menu builder route', function (): void {
    expect(MenuResource::getPages())->toHaveKey('builder');
});
