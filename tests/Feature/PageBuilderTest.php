<?php

declare(strict_types=1);

use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use KalimeroMK\FilamentPageBuilder\Contracts\DynamicTagContract;
use KalimeroMK\FilamentPageBuilder\Enums\PageStatus;
use KalimeroMK\FilamentPageBuilder\Events\BeforeElementRender;
use KalimeroMK\FilamentPageBuilder\Events\PageDraftSaved;
use KalimeroMK\FilamentPageBuilder\Events\PagePublished;
use KalimeroMK\FilamentPageBuilder\Facades\PageBuilder;
use KalimeroMK\FilamentPageBuilder\FilamentPageBuilderPlugin;
use KalimeroMK\FilamentPageBuilder\Models\Page;
use KalimeroMK\FilamentPageBuilder\Services\PageCacheService;
use KalimeroMK\FilamentPageBuilder\Support\HtmlSanitizer;
use KalimeroMK\FilamentPageBuilder\Widgets\AbstractWidget;

it('registers the core widgets by default with valid Heroicons', function (): void {
    $registry = PageBuilder::widgets();

    expect($registry->has('container'))->toBeTrue()
        ->and($registry->has('heading'))->toBeTrue()
        ->and($registry->has('text'))->toBeTrue()
        ->and($registry->has('button'))->toBeTrue()
        ->and($registry->get('container')::isContainer())->toBeTrue()
        ->and($registry->get('heading')::isContainer())->toBeFalse()
        ->and($registry->get('heading')::getIcon())->toBe('heroicon-o-h1');

    // Verify the SVG icon actually resolves without SvgNotFound on Linux
    expect(svg($registry->get('heading')::getIcon())->toHtml())->toContain('<svg');
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
                            'style' => 'secondary',
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
        ->toContain('fpb-button--secondary')
        ->toContain('Почни веднаш');

    expect($rendered->css)
        ->toContain('.fpb-el-hero_box { --fpb-padding: 48px;')
        ->toContain('--fpb-direction: row;')
        ->toContain('.fpb-button--secondary')
        ->toContain('@container fpb-canvas (max-width: 1024px) { .fpb-el-hero_box { --fpb-padding: 24px; } }')
        ->toContain('@container fpb-canvas (max-width: 767px) { .fpb-el-hero_box { --fpb-padding: 12px; --fpb-direction: column; --fpb-gap: 16px; } }')
        ->toContain('@container fpb-canvas (max-width: 767px) { .fpb-el-hero_title { --fpb-font-size: 28px; } }');
});

it('blocks advanced XSS payloads in HtmlSanitizer, URLs, and CssGenerator', function (): void {
    $dirtyHtml = '<p>Hello</p><a href="x"onclick="alert(1)">Click</a><svg/onload="alert(2)"><a href="javascript:alert(\'hello world\')">JS</a><a href="&#106;avascript:alert(3)">Entity</a>';
    $cleanHtml = HtmlSanitizer::clean($dirtyHtml);

    expect($cleanHtml)
        ->toContain('<p>Hello</p>')
        ->not->toContain('onclick')
        ->not->toContain('onload')
        ->not->toContain('javascript:');

    expect(HtmlSanitizer::sanitizeUrl("java\tscript:alert(1)"))->toBe('#')
        ->and(HtmlSanitizer::sanitizeUrl('javascript:alert(1)'))->toBe('#')
        ->and(HtmlSanitizer::sanitizeUrl('//evil.com/phish'))->toBe('#')
        ->and(HtmlSanitizer::sanitizeUrl('/contact-us'))->toBe('/contact-us')
        ->and(HtmlSanitizer::sanitizeUrl('https://laravel.com'))->toBe('https://laravel.com');

    $rendered = PageBuilder::renderSchema([
        'version' => 1,
        'elements' => [
            [
                'id' => 'css_test',
                'type' => 'heading',
                'settings' => [
                    'text' => 'CSS Injection Check',
                    'color' => '#111111; } /* body { display:none } */ url(https://evil.com)',
                ],
            ],
        ],
    ]);

    expect($rendered->css)
        ->not->toContain('/*')
        ->not->toContain('*/')
        ->not->toContain('url(');
});

it('handles the complete Draft -> Preview -> Publish -> Edit Draft -> Revision Restore -> Discard / Unpublish lifecycle', function (): void {
    Event::fake([PageDraftSaved::class, PagePublished::class]);

    $page = Page::create([
        'title' => 'Почетна Страница',
        'slug' => 'home',
        'meta' => [
            'meta_title' => 'SEO Почетна',
            'og_image' => 'https://example.com/og.png',
        ],
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

    // Public route returns 404 while page is only a draft
    $this->get('/pages/home')->assertNotFound();

    // Unsigned preview URL returns 403 even for an unauthorized logged-in user
    $regularUser = new class extends Authenticatable {};
    $this->actingAs($regularUser)->get("/_builder/preview/{$page->id}")->assertForbidden();

    // Authorized via Gate or signed URL returns 200
    Gate::define('view-page-builder-preview', fn (): bool => true);
    $this->actingAs($regularUser)->get("/_builder/preview/{$page->id}")->assertOk();

    $previewUrl = $page->getPreviewUrl();
    $this->get($previewUrl)
        ->assertOk()
        ->assertSee('Preview Mode (Draft)')
        ->assertSee('Верзија 1 (Нацрт)')
        ->assertSee('https://example.com/og.png', false);

    // Publish the page (v1)
    $page->publish('Initial launch');
    Event::assertDispatched(PagePublished::class);

    $cacheService = app(PageCacheService::class);
    $this->get('/pages/home')
        ->assertOk()
        ->assertSee('Верзија 1 (Нацрт)');

    // Cache should now be warm
    expect($cacheService->get($page))->not->toBeNull();

    // Saving a new draft after 5 seconds MUST NOT invalidate the live published cache!
    $this->travel(5)->seconds();
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

    expect($cacheService->get($page))->not->toBeNull()
        ->and($page->hasUnpublishedChanges())->toBeTrue();

    // Public visitors STILL see Version 1!
    $this->get('/pages/home')
        ->assertOk()
        ->assertSee('Верзија 1 (Нацрт)')
        ->assertDontSee('Верзија 2 (Само во Draft)');

    // Preview route shows Version 2!
    $this->get($page->getPreviewUrl())
        ->assertOk()
        ->assertSee('Верзија 2 (Само во Draft)');

    // Publish Version 2 and then restore Revision #1
    $page->publish('Second release');
    expect($page->revisions()->count())->toBe(2);

    $firstRevision = $page->revisions()->where('version_number', 1)->firstOrFail();
    $page->restoreRevision($firstRevision);

    expect($page->getWorkingSchema()['elements'][0]['settings']['text'])->toBe('Верзија 1 (Нацрт)');

    // Cross-page revision restore is rejected
    $otherPage = Page::create(['title' => 'Other', 'slug' => 'other']);
    expect(fn () => $otherPage->restoreRevision($firstRevision))->toThrow(InvalidArgumentException::class);

    // Discard draft & Unpublish
    $page->discardDraft();
    expect($page->hasUnpublishedChanges())->toBeFalse();

    $page->unpublish();
    expect($page->isDraft())->toBeTrue()
        ->and($cacheService->get($page))->toBeNull();
    $this->get('/pages/home')->assertNotFound();
});

it('supports Add-on API, protects $hidden model attributes in DynamicTags, and allows BeforeElementRender to toggle hide_element', function (): void {
    $secretRecord = new class extends Model
    {
        protected $guarded = [];

        protected $hidden = ['secret_token'];
    };
    $secretRecord->forceFill([
        'name' => 'Јавен Наслов',
        'secret_token' => 'TOP_SECRET_123',
    ]);

    $rendered = PageBuilder::renderSchema([
        'version' => 1,
        'elements' => [
            [
                'id' => 'visible_h',
                'type' => 'heading',
                'settings' => ['text' => 'Fallback'],
                'dynamic' => ['text' => 'model.attribute:name'],
            ],
            [
                'id' => 'secret_h',
                'type' => 'heading',
                'settings' => ['text' => 'Safe Fallback'],
                'dynamic' => ['text' => 'model.attribute:secret_token'],
            ],
        ],
    ], ['record' => $secretRecord]);

    expect($rendered->html)
        ->toContain('Јавен Наслов')
        ->toContain('Safe Fallback')
        ->not->toContain('TOP_SECRET_123');

    // Test BeforeElementRender hook dynamically hiding an element
    Event::listen(BeforeElementRender::class, function (BeforeElementRender $event): void {
        if (($event->element['id'] ?? '') === 'secret_h') {
            $event->settings['hide_element'] = true;
        }
    });

    $renderedAfterHook = PageBuilder::renderSchema([
        'version' => 1,
        'elements' => [
            [
                'id' => 'secret_h',
                'type' => 'heading',
                'settings' => ['text' => 'Should Be Hidden'],
            ],
        ],
    ]);

    expect($renderedAfterHook->html)->not->toContain('Should Be Hidden');

    // Test TemplateRegistry & Plugin config
    PageBuilder::templates()->register('hero-1', 'Hero Section', Page::emptySchema(), 'headers');
    expect(PageBuilder::templates()->has('hero-1'))->toBeTrue();

    $plugin = FilamentPageBuilderPlugin::make()
        ->navigationGroup('CMS')
        ->navigationSort(5);

    expect($plugin->getNavigationGroup())->toBe('CMS')
        ->and($plugin->getNavigationSort())->toBe(5);
});
