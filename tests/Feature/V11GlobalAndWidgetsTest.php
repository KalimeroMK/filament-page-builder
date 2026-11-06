<?php

declare(strict_types=1);

use KalimeroMK\FilamentPageBuilder\Enums\ThemeRegionType;
use KalimeroMK\FilamentPageBuilder\Facades\PageBuilder;
use KalimeroMK\FilamentPageBuilder\FormActions\AutoresponderAction;
use KalimeroMK\FilamentPageBuilder\Models\FormSubmission;
use KalimeroMK\FilamentPageBuilder\Models\Page;
use KalimeroMK\FilamentPageBuilder\Models\PageTemplate;
use KalimeroMK\FilamentPageBuilder\Models\ThemeTemplate;
use KalimeroMK\FilamentPageBuilder\Services\SiteStyleService;

it('stores and renders Global Site Settings as CSS variables', function (): void {
    $service = app(SiteStyleService::class);
    $service->save([
        'primary_color' => '#ff0000',
        'h1_font_size' => 56,
    ]);

    $css = $service->toCss();

    expect($css)
        ->toContain(':root')
        ->toContain('--fpb-global-primary: #ff0000;')
        ->toContain('--fpb-global-h1-size: 56px;');

    // Sanitization blocks CSS injection and invalid colors are rejected
    $service->save(['primary_color' => '#fff; } body { display:none } /*']);
    $cssAfterInjection = $service->toCss();
    expect($cssAfterInjection)->not->toContain('/*')
        ->and($cssAfterInjection)->not->toContain('display:none');

    // baseStyles includes the global :root block and consumer rules
    $service->save(['primary_color' => '#00ff00']);
    $rendered = PageBuilder::renderSchema(['version' => 1, 'elements' => []]);
    expect($rendered->css)->toContain('--fpb-global-primary: #00ff00;')
        ->and($rendered->css)->toContain('.fpb-canvas-root h1 { font-size: var(--fpb-global-h1-size');
});

it('renders Global Widget instances from Template Library with cycle protection', function (): void {
    $template = PageTemplate::create([
        'name' => 'Shared Hero',
        'schema' => [
            'version' => 1,
            'elements' => [
                ['id' => 'shared_h', 'type' => 'heading', 'settings' => ['text' => 'Споделен Наслов', 'tag' => 'h2']],
            ],
        ],
    ]);

    $rendered = PageBuilder::renderSchema([
        'version' => 1,
        'elements' => [
            [
                'id' => 'gw_1',
                'type' => 'global_widget',
                'settings' => ['global_id' => $template->id],
            ],
        ],
    ]);

    expect($rendered->html)->toContain('Споделен Наслов');

    // Missing template shows a config error instead of crashing
    $missing = PageBuilder::renderSchema([
        'version' => 1,
        'elements' => [
            ['id' => 'gw_2', 'type' => 'global_widget', 'settings' => ['global_id' => 99999]],
        ],
    ]);
    expect($missing->html)->toContain('Global template not found');
});

it('applies sanitized custom CSS and custom HTML attributes per element', function (): void {
    $rendered = PageBuilder::renderSchema([
        'version' => 1,
        'elements' => [
            [
                'id' => 'custom_1',
                'type' => 'heading',
                'settings' => [
                    'text' => 'Custom Styling',
                    'custom_css' => 'box-shadow: 0 4px 12px rgba(0,0,0,.15);',
                    'custom_attributes' => 'data-track="hero" aria-label="Banner"',
                ],
            ],
            [
                'id' => 'custom_2',
                'type' => 'heading',
                'settings' => [
                    'text' => 'XSS Attempt',
                    'custom_attributes' => 'onclick="alert(1)"',
                ],
            ],
        ],
    ]);

    expect($rendered->html)
        ->toContain('data-track="hero"')
        ->toContain('aria-label="Banner"')
        ->not->toContain('onclick')
        ->not->toContain('onmouseover');

    expect($rendered->css)->toContain('.fpb-el-custom_1 { box-shadow: 0 4px 12px rgba(0,0,0,.15); }');

    // First-position handler and backtick payloads are rejected
    $xss = PageBuilder::renderSchema([
        'version' => 1,
        'elements' => [
            [
                'id' => 'custom_3',
                'type' => 'heading',
                'settings' => [
                    'text' => 'XSS 2',
                    'custom_attributes' => 'onmouseover="alert`XSS`"',
                ],
            ],
        ],
    ]);
    expect($xss->html)->not->toContain('onmouseover')
        ->and($xss->html)->not->toContain('alert');

    // Global widget propagates nested CSS
    $styledTemplate = PageTemplate::create([
        'name' => 'Styled Global',
        'schema' => [
            'version' => 1,
            'elements' => [
                ['id' => 'sg_h', 'type' => 'heading', 'settings' => ['text' => 'Styled', 'color' => '#123456', 'font_size' => 33]],
            ],
        ],
    ]);

    $styledRender = PageBuilder::renderSchema([
        'version' => 1,
        'elements' => [
            ['id' => 'gw_styled', 'type' => 'global_widget', 'settings' => ['global_id' => $styledTemplate->id]],
        ],
    ]);
    expect($styledRender->html)->toContain('fpb-global-widget-styles')
        ->and($styledRender->html)->toContain('--fpb-color: #123456;');

    // Self-referencing global template is blocked as a cycle
    $cyclicTemplate = PageTemplate::create([
        'name' => 'Cyclic Global',
        'schema' => ['version' => 1, 'elements' => []],
    ]);
    $cyclicTemplate->update([
        'schema' => [
            'version' => 1,
            'elements' => [
                ['id' => 'cyc_gw', 'type' => 'global_widget', 'settings' => ['global_id' => $cyclicTemplate->id]],
            ],
        ],
    ]);

    $cyclicRender = PageBuilder::renderSchema([
        'version' => 1,
        'elements' => [
            ['id' => 'gw_cyc', 'type' => 'global_widget', 'settings' => ['global_id' => $cyclicTemplate->id]],
        ],
    ]);
    expect($cyclicRender->html)->toContain('Circular global widget reference');
});

it('renders custom 404 template when published, otherwise returns plain 404', function (): void {
    $this->get('/nonexistent-page-xyz')->assertNotFound();

    ThemeTemplate::create([
        'title' => 'Custom 404',
        'region' => ThemeRegionType::NotFound,
        'conditions' => [['type' => 'include', 'target' => 'entire_site', 'value' => '*']],
        'draft_content' => [
            'version' => 1,
            'elements' => [
                ['id' => 'nf_h', 'type' => 'heading', 'settings' => ['text' => 'Страницата не постои', 'tag' => 'h1']],
            ],
        ],
    ])->publish();

    $this->get('/nonexistent-page-xyz')
        ->assertStatus(404)
        ->assertSee('Страницата не постои');
});

it('sends autoresponder emails to the visitor email field', function (): void {
    \Illuminate\Support\Facades\Mail::shouldReceive('raw')->once()->andReturn(true);

    $page = new Page(['title' => 'Contact', 'slug' => 'contact']);
    $submission = new FormSubmission(['page_id' => 1, 'element_id' => 'form_1', 'data' => []]);

    $result = app(AutoresponderAction::class)->handle(
        ['subject' => 'Ви благодариме', 'message' => 'Ја добивме вашата порака.'],
        ['email' => 'visitor@example.com'],
        $submission,
        $page,
    );

    expect($result['success'])->toBeTrue();
});

it('rejects autoresponder when visitor email is invalid', function (): void {
    \Illuminate\Support\Facades\Mail::shouldReceive('raw')->never();

    $page = new Page(['title' => 'Contact', 'slug' => 'contact']);
    $submission = new FormSubmission(['page_id' => 1, 'element_id' => 'form_1', 'data' => []]);

    $failed = app(AutoresponderAction::class)->handle(
        [],
        ['email' => 'not-an-email'],
        $submission,
        $page,
    );

    expect($failed['success'])->toBeFalse();
});

it('escapes spreadsheet formulas in CSV export cells', function (): void {
    expect(\KalimeroMK\FilamentPageBuilder\Filament\Resources\FormSubmissionResource::escapeCsvCell('=1+1'))->toBe("'=1+1")
        ->and(\KalimeroMK\FilamentPageBuilder\Filament\Resources\FormSubmissionResource::escapeCsvCell('+SUM(1)'))->toBe("'+SUM(1)")
        ->and(\KalimeroMK\FilamentPageBuilder\Filament\Resources\FormSubmissionResource::escapeCsvCell('-2'))->toBe("'-2")
        ->and(\KalimeroMK\FilamentPageBuilder\Filament\Resources\FormSubmissionResource::escapeCsvCell('@cmd'))->toBe("'@cmd")
        ->and(\KalimeroMK\FilamentPageBuilder\Filament\Resources\FormSubmissionResource::escapeCsvCell('safe text'))->toBe('safe text');
});

it('registers and renders all 9 new v1.1 widgets with valid icons', function (): void {
    $registry = PageBuilder::widgets();

    $types = ['global_widget', 'slider', 'pricing_table', 'countdown', 'progress_bar', 'social_icons', 'star_rating', 'google_maps', 'flip_box', 'cta'];

    foreach ($types as $type) {
        expect($registry->has($type))->toBeTrue();
        expect(svg($registry->get($type)::getIcon())->toHtml())->toContain('<svg');
    }

    $rendered = PageBuilder::renderSchema([
        'version' => 1,
        'elements' => [
            ['id' => 's1', 'type' => 'slider', 'settings' => ['slides' => [['image' => 'https://example.com/a.jpg', 'title' => 'Слајд А']]]],
            ['id' => 'p1', 'type' => 'pricing_table', 'settings' => ['plan_name' => 'Про План', 'price' => '€29']],
            ['id' => 'c1', 'type' => 'countdown', 'settings' => ['target_date' => '2027-01-01 00:00']],
            ['id' => 'pb1', 'type' => 'progress_bar', 'settings' => ['percent' => 80, 'label' => 'Прогрес']],
            ['id' => 'so1', 'type' => 'social_icons', 'settings' => ['items' => [['network' => 'github', 'url' => 'https://github.com/KalimeroMK']]]],
            ['id' => 'sr1', 'type' => 'star_rating', 'settings' => ['rating' => 4.5, 'count_text' => '128 reviews']],
            ['id' => 'gm1', 'type' => 'google_maps', 'settings' => ['address' => 'Skopje']],
            ['id' => 'fb1', 'type' => 'flip_box', 'settings' => ['front_title' => 'Предна', 'back_title' => 'Задна']],
            ['id' => 'ct1', 'type' => 'cta', 'settings' => ['title' => 'Почни веднаш', 'button_label' => 'Клик']],
        ],
    ]);

    expect($rendered->html)
        ->toContain('Слајд А')
        ->toContain('Про План')
        ->toContain('€29')
        ->toContain('fpb-countdown')
        ->toContain('Прогрес')
        ->toContain('80%')
        ->toContain('github.com/KalimeroMK')
        ->toContain('128 reviews')
        ->toContain('google.com/maps?q=Skopje')
        ->toContain('Предна')
        ->toContain('Задна')
        ->toContain('Почни веднаш');
});
