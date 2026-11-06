<?php

declare(strict_types=1);

use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Event;
use KalimeroMK\FilamentPageBuilder\Contracts\DynamicTagContract;
use KalimeroMK\FilamentPageBuilder\Enums\PageStatus;
use KalimeroMK\FilamentPageBuilder\Events\PageDraftSaved;
use KalimeroMK\FilamentPageBuilder\Events\PagePublished;
use KalimeroMK\FilamentPageBuilder\Facades\PageBuilder;
use KalimeroMK\FilamentPageBuilder\Models\Page;
use KalimeroMK\FilamentPageBuilder\Widgets\AbstractWidget;

it('registers the 4 core v0.1 widgets by default', function (): void {
    $registry = PageBuilder::widgets();

    expect($registry->has('container'))->toBeTrue()
        ->and($registry->has('heading'))->toBeTrue()
        ->and($registry->has('text'))->toBeTrue()
        ->and($registry->has('button'))->toBeTrue()
        ->and($registry->get('container')::isContainer())->toBeTrue()
        ->and($registry->get('heading')::isContainer())->toBeFalse();
});

it('builds Filament v5 3-tab inspector schemas (Content / Style / Advanced) for widgets', function (): void {
    $heading = PageBuilder::widgets()->get('heading');
    expect($heading)->not->toBeNull();

    $schema = $heading->getSchema(Schema::make());

    expect($schema)->toBeInstanceOf(Schema::class)
        ->and($schema->getComponents())->not->toBeEmpty();
});

it('renders nested Flexbox container, heading, sanitized text, button and responsive CSS variables', function (): void {
    $schema = [
        'version' => 1,
        'elements' => [
            [
                'id' => 'hero_box',
                'type' => 'container',
                'settings' => [
                    'html_tag' => 'section',
                    'direction' => 'row',
                    'direction_mobile' => 'column',
                    'gap' => 32,
                    'gap_mobile' => 16,
                    'padding' => 48,
                    'padding_tablet' => 24,
                    'padding_mobile' => 12,
                    'background_color' => '#f8fafc',
                ],
                'children' => [
                    [
                        'id' => 'hero_title',
                        'type' => 'heading',
                        'settings' => [
                            'text' => 'Добредојдовте во Filament Page Builder',
                            'tag' => 'h1',
                            'color' => '#0f172a',
                            'font_size' => 44,
                            'font_size_mobile' => 28,
                        ],
                    ],
                    [
                        'id' => 'hero_desc',
                        'type' => 'text',
                        'settings' => [
                            'content' => '<p> Чист текст </p><script>alert("xss")</script>',
                        ],
                    ],
                    [
                        'id' => 'hero_cta',
                        'type' => 'button',
                        'settings' => [
                            'label' => 'Почни веднаш',
                            'link' => '/start',
                            'style' => 'primary',
                        ],
                    ],
                ],
            ],
        ],
    ];

    $rendered = PageBuilder::renderSchema($schema);

    expect($rendered->html)
        ->toContain('data-fpb-id="hero_box"')
        ->toContain('<h1')
        ->toContain('Добредојдовте во Filament Page Builder')
        ->toContain('<p> Чист текст </p>')
        ->not->toContain('<script>')
        ->toContain('href="/start"')
        ->toContain('Почни веднаш');

    expect($rendered->css)
        ->toContain('.fpb-el-hero_box { --fpb-padding: 48px;')
        ->toContain('--fpb-direction: row;')
        ->toContain('@container fpb-canvas (max-width: 1024px) { .fpb-el-hero_box { --fpb-padding: 24px; } }')
        ->toContain('@container fpb-canvas (max-width: 767px) { .fpb-el-hero_box { --fpb-padding: 12px; --fpb-direction: column; --fpb-gap: 16px; } }')
        ->toContain('@container fpb-canvas (max-width: 767px) { .fpb-el-hero_title { --fpb-font-size: 28px; } }');
});

it('handles the complete Draft -> Preview -> Publish -> Edit Draft -> Discard / Unpublish lifecycle', function (): void {
    Event::fake([PageDraftSaved::class, PagePublished::class]);

    // 1. Create a page in Draft mode
    $page = Page::create([
        'title' => 'Почетна Страница',
        'slug' => 'home',
        'draft_content' => [
            'version' => 1,
            'elements' => [
                [
                    'id' => 'h1_draft',
                    'type' => 'heading',
                    'settings' => ['text' => 'Верзија 1 (Нацрт)', 'tag' => 'h1'],
                ],
            ],
        ],
    ]);

    expect($page->status)->toBe(PageStatus::Draft)
        ->and($page->isDraft())->toBeTrue()
        ->and($page->isPublished())->toBeFalse()
        ->and($page->hasUnpublishedChanges())->toBeTrue()
        ->and($page->content)->toBeNull();

    // 2. Public route returns 404 while page is only a draft
    $this->get('/pages/home')->assertNotFound();

    // 3. Unsigned preview URL returns 403, signed preview URL returns 200 and renders draft_content + preview toolbar
    $this->get("/_builder/preview/{$page->id}")->assertForbidden();

    $previewUrl = $page->getPreviewUrl();
    $this->get($previewUrl)
        ->assertOk()
        ->assertSee('Preview Mode (Draft)')
        ->assertSee('Верзија 1 (Нацрт)');

    // 4. Publish the page
    $page->publish('Initial launch');
    Event::assertDispatched(PagePublished::class);

    expect($page->isPublished())->toBeTrue()
        ->and($page->hasUnpublishedChanges())->toBeFalse()
        ->and($page->revisions()->count())->toBe(1)
        ->and($page->revisions()->first()->version_number)->toBe(1);

    // 5. Public route now serves the published content
    $this->get('/pages/home')
        ->assertOk()
        ->assertSee('Верзија 1 (Нацрт)')
        ->assertDontSee('Preview Mode (Draft)');

    // 6. Save new changes as Draft WITHOUT publishing
    $page->saveDraft([
        'version' => 1,
        'elements' => [
            [
                'id' => 'h1_draft',
                'type' => 'heading',
                'settings' => ['text' => 'Верзија 2 (Само во Draft)', 'tag' => 'h1'],
            ],
        ],
    ]);

    Event::assertDispatched(PageDraftSaved::class);
    expect($page->hasUnpublishedChanges())->toBeTrue();

    // Public visitors STILL see Version 1!
    $this->get('/pages/home')
        ->assertOk()
        ->assertSee('Верзија 1 (Нацрт)')
        ->assertDontSee('Верзија 2 (Само во Draft)');

    // Preview route shows the new Version 2 draft!
    $this->get($page->getPreviewUrl())
        ->assertOk()
        ->assertSee('Верзија 2 (Само во Draft)')
        ->assertSee('Has unpublished changes');

    // 7. Discard draft changes -> resets draft_content back to published Version 1
    $page->discardDraft();
    expect($page->hasUnpublishedChanges())->toBeFalse();

    $this->get($page->getPreviewUrl())
        ->assertOk()
        ->assertSee('Верзија 1 (Нацрт)')
        ->assertDontSee('Верзија 2 (Само во Draft)');

    // 8. Unpublish page -> moves back to Draft and hides from public route
    $page->unpublish();
    expect($page->isDraft())->toBeTrue();
    $this->get('/pages/home')->assertNotFound();
});

it('supports the Add-on API for registering custom widgets and dynamic tag providers', function (): void {
    // Register a custom dynamic tag
    $customTag = new class implements DynamicTagContract
    {
        public static function getName(): string
        {
            return 'addon.promo_badge';
        }

        public static function getLabel(): string
        {
            return 'Promo Badge';
        }

        public static function getGroup(): string
        {
            return 'Add-on';
        }

        public function resolve(array $parameters = [], array $context = []): mixed
        {
            return 'BLACK FRIDAY -50%';
        }
    };

    PageBuilder::dynamicTags()->register($customTag);

    // Register a custom add-on widget
    $customWidget = new class extends AbstractWidget
    {
        public static function getType(): string
        {
            return 'promo_banner';
        }

        public static function getLabel(): string
        {
            return 'Promo Banner';
        }

        public static function getIcon(): string
        {
            return 'heroicon-o-sparkles';
        }

        public static function getDefaultSettings(): array
        {
            return ['badge_text' => 'Default Promo'];
        }

        public function getContentSchema(): array
        {
            return [];
        }

        public function getStyleSchema(): array
        {
            return [];
        }

        public function getCssMap(): array
        {
            return [];
        }

        protected function getViewName(): string
        {
            return 'filament-page-builder::widgets.heading';
        }

        public function render(array $element, array $settings, string $childrenHtml = '', array $context = []): string
        {
            return '<div class="addon-promo">' . e((string) $settings['badge_text']) . '</div>';
        }
    };

    PageBuilder::widgets()->register($customWidget);

    $rendered = PageBuilder::renderSchema([
        'version' => 1,
        'elements' => [
            [
                'id' => 'promo_1',
                'type' => 'promo_banner',
                'settings' => ['badge_text' => 'Static Text'],
                'dynamic' => ['badge_text' => 'addon.promo_badge'],
            ],
        ],
    ]);

    expect($rendered->html)->toContain('<div class="addon-promo">BLACK FRIDAY -50%</div>');
});
