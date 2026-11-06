<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use KalimeroMK\FilamentPageBuilder\Models\FormSubmission;
use KalimeroMK\FilamentPageBuilder\Models\Page;
use KalimeroMK\FilamentPageBuilder\Models\Popup;
use KalimeroMK\FilamentPageBuilder\Services\PopupResolver;

it('resolves published popups by display conditions and renders them on the frontend', function (): void {
    $globalPopup = Popup::create([
        'title' => 'Newsletter Popup',
        'draft_content' => [
            'version' => 1,
            'elements' => [
                ['id' => 'pop_h', 'type' => 'heading', 'settings' => ['text' => 'Претплати се!', 'tag' => 'h2']],
            ],
        ],
        'trigger_settings' => ['type' => 'on_load', 'delay_seconds' => 2, 'max_width' => 560, 'show_once_per_visitor' => true],
        'conditions' => [['type' => 'include', 'target' => 'entire_site', 'value' => '*']],
    ])->publish();

    $excludedPopup = Popup::create([
        'title' => 'Checkout Only Popup',
        'draft_content' => [
            'version' => 1,
            'elements' => [
                ['id' => 'pop_c', 'type' => 'heading', 'settings' => ['text' => 'Checkout Offer', 'tag' => 'h2']],
            ],
        ],
        'conditions' => [['type' => 'include', 'target' => 'page_slug', 'value' => 'checkout']],
    ])->publish();

    $draftPopup = Popup::create([
        'title' => 'Draft Popup',
        'draft_content' => [
            'version' => 1,
            'elements' => [
                ['id' => 'pop_d', 'type' => 'heading', 'settings' => ['text' => 'Should Not Appear', 'tag' => 'h2']],
            ],
        ],
    ]);

    $page = Page::create([
        'title' => 'Home',
        'slug' => 'home-popup',
        'draft_content' => ['version' => 1, 'elements' => []],
    ])->publish();

    $active = app(PopupResolver::class)->activePopups(['page' => $page]);
    expect($active->pluck('id')->toArray())->toContain($globalPopup->id)
        ->and($active->pluck('id')->toArray())->not->toContain($excludedPopup->id)
        ->and($active->pluck('id')->toArray())->not->toContain($draftPopup->id);

    $this->get($page->getPublicUrl())
        ->assertOk()
        ->assertSee('Претплати се!')
        ->assertSee('fpb_popup_' . $globalPopup->id)
        ->assertDontSee('Checkout Offer')
        ->assertDontSee('Should Not Appear');
});

it('skips validation for conditionally hidden form fields', function (): void {
    $page = Page::create([
        'title' => 'Conditional Form',
        'slug' => 'conditional-form',
        'draft_content' => [
            'version' => 1,
            'elements' => [
                [
                    'id' => 'form_cond',
                    'type' => 'form',
                    'settings' => [
                        'fields' => [
                            ['name' => 'contact_method', 'type' => 'select', 'label' => 'Contact Method', 'required' => true, 'options' => 'Email, Phone'],
                            ['name' => 'phone_number', 'type' => 'text', 'label' => 'Phone', 'required' => true, 'visible_if_field' => 'contact_method', 'visible_if_value' => 'Phone'],
                        ],
                        'actions' => [['action' => 'save_to_database', 'settings' => []]],
                        'honeypot' => false,
                    ],
                ],
            ],
        ],
    ])->publish();

    // Phone field is hidden when contact_method = Email, so it should not be required
    $this->post("/_builder/form/{$page->id}/form_cond", [
        'contact_method' => 'Email',
    ])->assertRedirect();

    $submission = FormSubmission::firstOrFail();
    expect($submission->data)->not->toHaveKey('phone_number');

    // When contact_method = Phone, phone_number is required
    $this->post("/_builder/form/{$page->id}/form_cond", [
        'contact_method' => 'Phone',
    ])->assertSessionHasErrors(['phone_number'], null, 'fpb_form_cond');
});

it('verifies Cloudflare Turnstile when enabled', function (): void {
    config()->set('filament-page-builder.forms.turnstile.secret_key', 'test-secret');
    config()->set('filament-page-builder.forms.turnstile.site_key', 'test-site-key');

    Http::fake([
        'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response(['success' => true], 200),
    ]);

    $page = Page::create([
        'title' => 'Turnstile Form',
        'slug' => 'turnstile-form',
        'draft_content' => [
            'version' => 1,
            'elements' => [
                [
                    'id' => 'form_ts',
                    'type' => 'form',
                    'settings' => [
                        'fields' => [
                            ['name' => 'name', 'type' => 'text', 'label' => 'Name', 'required' => true],
                        ],
                        'actions' => [['action' => 'save_to_database', 'settings' => []]],
                        'honeypot' => false,
                        'turnstile' => true,
                    ],
                ],
            ],
        ],
    ])->publish();

    $this->post("/_builder/form/{$page->id}/form_ts", [
        'name' => 'Зоран',
        'cf-turnstile-response' => 'valid-token',
    ])->assertRedirect();

    expect(FormSubmission::count())->toBe(1);
});

it('rejects submissions when Turnstile verification fails', function (): void {
    config()->set('filament-page-builder.forms.turnstile.secret_key', 'test-secret');
    config()->set('filament-page-builder.forms.turnstile.site_key', 'test-site-key');

    Http::fake([
        'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response(['success' => false], 200),
    ]);

    $page = Page::create([
        'title' => 'Turnstile Form',
        'slug' => 'turnstile-form',
        'draft_content' => [
            'version' => 1,
            'elements' => [
                [
                    'id' => 'form_ts',
                    'type' => 'form',
                    'settings' => [
                        'fields' => [
                            ['name' => 'name', 'type' => 'text', 'label' => 'Name', 'required' => true],
                        ],
                        'actions' => [['action' => 'save_to_database', 'settings' => []]],
                        'honeypot' => false,
                        'turnstile' => true,
                    ],
                ],
            ],
        ],
    ])->publish();

    $this->post("/_builder/form/{$page->id}/form_ts", [
        'name' => 'Bot',
        'cf-turnstile-response' => 'bad-token',
    ])->assertSessionHasErrors(['cf-turnstile-response'], null, 'fpb_form_ts');

    expect(FormSubmission::count())->toBe(0);
});


it('processes form submissions embedded inside popups', function (): void {
    $popup = Popup::create([
        'title' => 'Popup Form',
        'draft_content' => [
            'version' => 1,
            'elements' => [
                [
                    'id' => 'popup_form_1',
                    'type' => 'form',
                    'settings' => [
                        'fields' => [
                            ['name' => 'email', 'type' => 'email', 'label' => 'Email', 'required' => true],
                        ],
                        'actions' => [['action' => 'save_to_database', 'settings' => []]],
                        'honeypot' => false,
                    ],
                ],
            ],
        ],
    ])->publish();

    $this->post("/_builder/popup-form/{$popup->id}/popup_form_1", [
        'email' => 'subscriber@example.com',
    ])->assertRedirect();

    $submission = FormSubmission::firstOrFail();
    expect($submission->page_id)->toBe($popup->id)
        ->and($submission->element_id)->toBe('popup_form_1')
        ->and($submission->data['email'])->toBe('subscriber@example.com');
});

it('fails closed when Turnstile is enabled but keys are incomplete', function (): void {
    config()->set('filament-page-builder.forms.turnstile.site_key', 'site-key-only');
    config()->set('filament-page-builder.forms.turnstile.secret_key', '');

    $page = Page::create([
        'title' => 'Turnstile Misconfig',
        'slug' => 'turnstile-misconfig',
        'draft_content' => [
            'version' => 1,
            'elements' => [
                [
                    'id' => 'form_mc',
                    'type' => 'form',
                    'settings' => [
                        'fields' => [
                            ['name' => 'name', 'type' => 'text', 'label' => 'Name', 'required' => true],
                        ],
                        'actions' => [['action' => 'save_to_database', 'settings' => []]],
                        'honeypot' => false,
                        'turnstile' => true,
                    ],
                ],
            ],
        ],
    ])->publish();

    $this->post("/_builder/form/{$page->id}/form_mc", [
        'name' => 'Зоран',
    ])->assertSessionHasErrors(['cf-turnstile-response'], null, 'fpb_form_mc');

    expect(FormSubmission::count())->toBe(0);
});
