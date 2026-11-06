<?php

declare(strict_types=1);

use KalimeroMK\FilamentPageBuilder\Enums\ThemeRegionType;
use KalimeroMK\FilamentPageBuilder\Facades\PageBuilder;
use KalimeroMK\FilamentPageBuilder\Livewire\VisualPageBuilderEditor;
use KalimeroMK\FilamentPageBuilder\Models\Page;
use KalimeroMK\FilamentPageBuilder\Models\ThemeTemplate;
use Livewire\Livewire;

it('resolves and renders Theme Builder regions (Header, Footer, Single, Archive) with Include/Exclude conditions and priorities', function (): void {
    // All ThemeRegionType icons must resolve without SvgNotFound
    foreach (ThemeRegionType::cases() as $regionCase) {
        expect(svg($regionCase->getIcon())->toHtml())->toContain('<svg');
    }

    // 1. Global Header (priority 10) for entire site EXCEPT page_slug = 'checkout' (and an empty exclude rule that must be ignored)
    $globalHeader = ThemeTemplate::create([
        'title' => 'Global Header',
        'region' => ThemeRegionType::Header,
        'priority' => 10,
        'conditions' => [
            ['type' => 'include', 'target' => 'entire_site', 'value' => '*'],
            ['type' => 'exclude', 'target' => 'page_slug', 'value' => 'checkout'],
            ['type' => 'exclude', 'target' => 'path', 'value' => ''],
        ],
        'draft_content' => [
            'version' => 1,
            'elements' => [
                [
                    'id' => 'hdr_global',
                    'type' => 'heading',
                    'settings' => ['text' => 'Главно Мени', 'tag' => 'h2'],
                ],
            ],
        ],
    ]);
    $globalHeader->publish();

    // 2. Blog-specific Header (priority 50 - higher priority wins on /blog/* paths)
    $blogHeader = ThemeTemplate::create([
        'title' => 'Blog Header',
        'region' => ThemeRegionType::Header,
        'priority' => 50,
        'conditions' => [
            ['type' => 'include', 'target' => 'path', 'value' => 'blog/*'],
        ],
        'draft_content' => [
            'version' => 1,
            'elements' => [
                [
                    'id' => 'hdr_blog',
                    'type' => 'heading',
                    'settings' => ['text' => 'Блог Навигација', 'tag' => 'h2'],
                ],
            ],
        ],
    ]);
    $blogHeader->publish();

    // 3. Global Footer
    $globalFooter = ThemeTemplate::create([
        'title' => 'Global Footer',
        'region' => ThemeRegionType::Footer,
        'priority' => 10,
        'conditions' => [
            ['type' => 'include', 'target' => 'entire_site', 'value' => '*'],
        ],
        'draft_content' => [
            'version' => 1,
            'elements' => [
                [
                    'id' => 'ftr_copy',
                    'type' => 'heading',
                    'settings' => ['text' => 'Сите права се задржани 2026', 'tag' => 'h4'],
                ],
            ],
        ],
    ]);
    $globalFooter->publish();

    // 4. Single Model Template (matches model = Page)
    $singlePageTemplate = ThemeTemplate::create([
        'title' => 'Single Page Template',
        'region' => ThemeRegionType::Single,
        'priority' => 20,
        'conditions' => [
            ['type' => 'include', 'target' => 'model', 'value' => Page::class],
        ],
        'draft_content' => [
            'version' => 1,
            'elements' => [
                ['id' => 'sgl_h', 'type' => 'heading', 'settings' => ['text' => 'Single Layout Active', 'tag' => 'h3']],
            ],
        ],
    ])->publish();

    // 5. Archive Template (matches route = blog.index)
    $archiveTemplate = ThemeTemplate::create([
        'title' => 'Blog Archive Template',
        'region' => ThemeRegionType::Archive,
        'priority' => 20,
        'conditions' => [
            ['type' => 'include', 'target' => 'route', 'value' => 'blog.*'],
        ],
        'draft_content' => [
            'version' => 1,
            'elements' => [
                ['id' => 'arc_h', 'type' => 'heading', 'settings' => ['text' => 'Archive Listing Active', 'tag' => 'h3']],
            ],
        ],
    ])->publish();

    // Test on a standard page ('about') -> renders Global Header + Page Body + Global Footer
    $aboutPage = Page::create([
        'title' => 'За Нас',
        'slug' => 'about',
        'layout' => 'default',
        'draft_content' => [
            'version' => 1,
            'elements' => [
                ['id' => 'body_h1', 'type' => 'heading', 'settings' => ['text' => 'Содржина За Нас', 'tag' => 'h1']],
            ],
        ],
    ])->publish();

    $this->get('/pages/about')
        ->assertOk()
        ->assertSee('Главно Мени')
        ->assertSee('Содржина За Нас')
        ->assertSee('Сите права се задржани 2026');

    // Test on 'checkout' page -> Global Header is excluded by condition
    Page::create([
        'title' => 'Наплата',
        'slug' => 'checkout',
        'layout' => 'default',
        'draft_content' => [
            'version' => 1,
            'elements' => [
                ['id' => 'chk_h1', 'type' => 'heading', 'settings' => ['text' => 'Кошничка', 'tag' => 'h1']],
            ],
        ],
    ])->publish();

    $this->get('/pages/checkout')
        ->assertOk()
        ->assertDontSee('Главно Мени')
        ->assertSee('Кошничка')
        ->assertSee('Сите права се задржани 2026');

    // Test higher-priority Blog Header matching path 'blog/first-post'
    $resolvedForBlog = PageBuilder::theme()->resolve(ThemeRegionType::Header, ['path' => 'blog/first-post']);
    expect($resolvedForBlog?->id)->toBe($blogHeader->id);

    $renderedBlogHeader = PageBuilder::renderThemeRegion('header', ['path' => 'blog/first-post']);
    expect($renderedBlogHeader->html)->toContain('Блог Навигација');

    // Test Single template matching both object and class-string model context
    expect(PageBuilder::theme()->resolve(ThemeRegionType::Single, ['record' => $aboutPage])?->id)->toBe($singlePageTemplate->id)
        ->and(PageBuilder::theme()->resolve(ThemeRegionType::Single, ['model' => 'Page'])?->id)->toBe($singlePageTemplate->id);

    // Test Archive template matching route_name wildcard
    expect(PageBuilder::theme()->resolve(ThemeRegionType::Archive, ['route_name' => 'blog.index'])?->id)->toBe($archiveTemplate->id)
        ->and(PageBuilder::theme()->resolve(ThemeRegionType::Archive, ['route_name' => 'shop.index']))->toBeNull();
});

it('displays the Navigator element tree and toggles element visibility in VisualPageBuilderEditor', function (): void {
    $page = Page::create([
        'title' => 'Navigator Page',
        'slug' => 'navigator-page',
    ]);

    $editor = Livewire::test(VisualPageBuilderEditor::class, ['page' => $page]);

    $editor->call('addWidget', 'container');
    $containerId = $editor->get('activeElementId');

    $editor->call('addWidget', 'heading', $containerId);
    $headingId = $editor->get('activeElementId');

    // Switch to the Navigator tab and verify both container and nested heading appear in the tree
    $editor->call('setLeftTab', 'navigator')
        ->assertSet('leftTab', 'navigator')
        ->assertSee('Navigator (Element Tree)')
        ->assertSee('#' . $containerId)
        ->assertSee('#' . $headingId);

    $editor->call('selectElement', $containerId)
        ->call('updateActiveElementSettings', ['admin_label' => 'Featured stories'])
        ->call('selectElement', $headingId)
        ->call('updateActiveElementSettings', ['admin_label' => 'Main headline'])
        ->call('setLeftTab', 'navigator')
        ->assertSee('Featured stories · Flex Container')
        ->assertSee('Main headline')
        ->assertSee('Heading')
        ->assertSee('border-left: 2px solid #dbeafe;', false);

    // Toggle visibility of the heading via Navigator
    $editor->call('toggleElementVisibility', $headingId);
    $schemaAfterHide = $editor->get('schema');
    expect($schemaAfterHide['elements'][0]['children'][0]['settings']['hide_element'])->toBeTrue();

    // Toggle visibility back on
    $editor->call('toggleElementVisibility', $headingId);
    $schemaAfterShow = $editor->get('schema');
    expect($schemaAfterShow['elements'][0]['children'][0]['settings']['hide_element'])->toBeFalse();
});
