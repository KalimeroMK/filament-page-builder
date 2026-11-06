<?php

declare(strict_types=1);

use KalimeroMK\FilamentPageBuilder\Facades\PageBuilder;
use KalimeroMK\FilamentPageBuilder\Livewire\VisualPageBuilderEditor;
use KalimeroMK\FilamentPageBuilder\Models\Menu;
use KalimeroMK\FilamentPageBuilder\Models\Page;
use KalimeroMK\FilamentPageBuilder\Services\CustomAssetsService;
use KalimeroMK\FilamentPageBuilder\Services\SchemaDiffService;
use KalimeroMK\FilamentPageBuilder\Support\PageBuilderAuthorizer;
use Livewire\Livewire;

it('renders NavMenuWidget with off-canvas mobile menu and sanitized links', function (): void {
    $menu = Menu::create([
        'title' => 'Main Menu',
        'slug' => 'main',
        'items' => [
            ['label' => 'Почетна', 'url' => '/pages/home', 'target' => '_self'],
            ['label' => 'Контакт', 'url' => '/pages/contact', 'target' => '_blank'],
            ['label' => 'XSS Link', 'url' => 'javascript:alert(1)'],
            ['label' => 'Empty URL', 'url' => ''],
        ],
    ]);

    expect(count($menu->items))->toBe(2);

    $rendered = PageBuilder::renderSchema([
        'version' => 1,
        'elements' => [
            [
                'id' => 'nav_1',
                'type' => 'nav_menu',
                'settings' => ['menu_id' => $menu->id, 'orientation' => 'horizontal'],
            ],
        ],
    ]);

    expect($rendered->html)
        ->toContain('Почетна')
        ->toContain('Контакт')
        ->toContain('fpb-navmenu-toggle')
        ->toContain('Mobile navigation')
        ->not->toContain('javascript:');
});

it('applies motion, sticky and device visibility classes in widgets', function (): void {
    $rendered = PageBuilder::renderSchema([
        'version' => 1,
        'elements' => [
            [
                'id' => 'anim_1',
                'type' => 'heading',
                'settings' => [
                    'text' => 'Animated',
                    'animation' => 'slide-up',
                    'sticky' => 'top',
                    'sticky_offset' => 20,
                    'hide_on_mobile' => true,
                ],
            ],
        ],
    ]);

    expect($rendered->html)
        ->toContain('fpb-anim-slide-up')
        ->toContain('fpb-sticky-top')
        ->toContain('fpb-hide-mobile');

    expect($rendered->css)
        ->toContain('@keyframes fpb-slide-up')
        ->toContain('.fpb-hide-mobile { display: none !important; }')
        ->toContain('--fpb-sticky-offset: 20px;');
});

it('renders container background images with parallax and rejects unsafe URLs', function (): void {
    $rendered = PageBuilder::renderSchema([
        'version' => 1,
        'elements' => [
            [
                'id' => 'parallax_hero',
                'type' => 'container',
                'settings' => [
                    'background_image' => 'https://images.example.com/hero.jpg?fit=crop&w=1600',
                    'background_size' => 'cover',
                    'background_position' => 'center',
                    'background_repeat' => 'no-repeat',
                    'background_attachment' => 'fixed',
                ],
            ],
            [
                'id' => 'unsafe_background',
                'type' => 'container',
                'settings' => ['background_image' => 'javascript:alert(1)'],
            ],
            [
                'id' => 'css_background',
                'type' => 'container',
                'settings' => ['background_image' => 'https://example.com/bad");color:red;/*'],
            ],
        ],
    ]);

    expect($rendered->css)
        ->toContain('background-image: var(--fpb-bg-image, none);')
        ->toContain('--fpb-bg-image: url("https://images.example.com/hero.jpg?fit=crop&w=1600");')
        ->toContain('--fpb-bg-size: cover;')
        ->toContain('--fpb-bg-position: center;')
        ->toContain('--fpb-bg-repeat: no-repeat;')
        ->toContain('--fpb-bg-attachment: fixed;')
        ->not->toContain('javascript:')
        ->not->toContain('color:red');
});

it('registers custom fonts and custom icons with sanitization', function (): void {
    $assets = app(CustomAssetsService::class);

    $assets->saveFonts([
        ['family' => 'Inter', 'css_url' => 'https://fonts.example.com/inter.css'],
        ['family' => 'Bad URL', 'css_url' => 'http://insecure.com/font.css'],
        ['family' => 'XSS', 'css_url' => 'javascript:alert(1)'],
    ]);

    $css = $assets->fontsToCss();
    expect($css)
        ->toContain('@import url("https://fonts.example.com/inter.css")')
        ->not->toContain('insecure.com')
        ->not->toContain('javascript:');

    $svg = $assets->sanitizeSvg('<svg viewBox="0 0 24 24" onload="alert(1)"><path d="M0 0h24v24H0z"/></svg>');
    expect($svg)->not->toContain('onload')
        ->and($svg)->toContain('<path');

    // DOM-based sanitizer rejects no-whitespace handlers, scripts, and keeps a single svg root
    expect($assets->sanitizeSvg('<svg/onload=alert(1)></svg>'))->toBe('')
        ->and($assets->sanitizeSvg('<svg><script>alert(1)</script><path d="M0 0"/></svg>'))->not->toContain('<script')
        ->and($assets->sanitizeSvg('<svg><a xlink:href="javascript:alert(1)"><path d="M0 0"/></a></svg>'))->not->toContain('javascript:');
});

it('preserves menu items on partial updates and renders mobile submenu children', function (): void {
    $menu = Menu::create([
        'title' => 'Main Menu',
        'slug' => 'main',
        'items' => [
            ['label' => 'Услуги', 'url' => '/services', 'children' => [
                ['label' => 'Веб Дизајн', 'url' => '/services/web-design'],
            ]],
        ],
    ]);

    // Partial update without touching items must not wipe them
    $menu->update(['title' => 'Updated Title']);
    expect($menu->fresh()->items)->not->toBeEmpty();

    $rendered = PageBuilder::renderSchema([
        'version' => 1,
        'elements' => [
            [
                'id' => 'nav_mobile',
                'type' => 'nav_menu',
                'settings' => ['menu_id' => $menu->id],
            ],
        ],
    ]);

    expect($rendered->html)->toContain('Веб Дизајн')
        ->and($rendered->html)->toContain('fpb-navmenu-toggle { display: none; }');
});

it('detects sibling reordering in SchemaDiffService', function (): void {
    $diff = app(SchemaDiffService::class)->compare(
        [
            ['id' => 'a', 'type' => 'heading', 'settings' => ['text' => 'A']],
            ['id' => 'b', 'type' => 'button', 'settings' => ['label' => 'B']],
        ],
        [
            ['id' => 'b', 'type' => 'button', 'settings' => ['label' => 'B']],
            ['id' => 'a', 'type' => 'heading', 'settings' => ['text' => 'A']],
        ],
    );

    expect($diff['reordered'])->toBe(1)
        ->and($diff['changed'])->toBe(0);
});

it('supports the role manager Gate for builder areas', function (): void {
    expect(PageBuilderAuthorizer::can('pages'))->toBeTrue();

    config()->set('filament-page-builder.authorization.access_callback', fn ($user, string $area): bool => $area !== 'menus');
    \Illuminate\Support\Facades\Gate::define('fpb.access_builder', function ($user = null, ...$arguments): bool {
        $area = $arguments[0] ?? 'builder';
        $callback = config('filament-page-builder.authorization.access_callback');

        return $callback instanceof Closure ? (bool) $callback($user, $area) : true;
    });

    expect(PageBuilderAuthorizer::can('menus'))->toBeFalse()
        ->and(PageBuilderAuthorizer::can('pages'))->toBeTrue();
});

it('compares revisions with SchemaDiffService and the editor Compare action', function (): void {
    $page = Page::create([
        'title' => 'Diff Page',
        'slug' => 'diff-page',
        'draft_content' => [
            'version' => 1,
            'elements' => [
                ['id' => 'h1', 'type' => 'heading', 'settings' => ['text' => 'Original']],
            ],
        ],
    ])->publish();

    $diff = app(SchemaDiffService::class)->compare(
        [['id' => 'h1', 'type' => 'heading', 'settings' => ['text' => 'Original']]],
        [
            ['id' => 'h1', 'type' => 'heading', 'settings' => ['text' => 'Changed']],
            ['id' => 'b1', 'type' => 'button', 'settings' => ['label' => 'New']],
        ],
    );

    expect($diff['added'])->toBe(1)
        ->and($diff['removed'])->toBe(0)
        ->and($diff['changed'])->toBe(1)
        ->and($diff['by_type']['heading']['changed'])->toBe(1)
        ->and($diff['by_type']['button']['added'])->toBe(1);

    $editor = Livewire::test(VisualPageBuilderEditor::class, ['page' => $page]);
    $revisionId = $page->revisions()->firstOrFail()->id;

    $editor->call('updateActiveElementSettings', ['text' => 'unused'])
        ->call('addWidget', 'heading');

    $editor->call('compareRevision', $revisionId);
    expect($editor->get('revisionDiff'))->not->toBeNull()
        ->and($editor->get('revisionDiff')['added'])->toBeGreaterThan(0);
});
