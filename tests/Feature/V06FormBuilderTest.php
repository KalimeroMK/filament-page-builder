<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use KalimeroMK\FilamentPageBuilder\Models\FormSubmission;
use KalimeroMK\FilamentPageBuilder\Models\Page;

function createFormPage(array $actions = [['action' => 'save_to_database', 'settings' => []]], array $fields = []): Page
{
    static $counter = 0;
    $counter++;

    if ($fields === []) {
        $fields = [
            ['name' => 'name', 'type' => 'text', 'label' => 'Name', 'required' => true],
            ['name' => 'email', 'type' => 'email', 'label' => 'Email', 'required' => true],
            ['name' => 'message', 'type' => 'textarea', 'label' => 'Message', 'required' => false],
        ];
    }

    return Page::create([
        'title' => 'Контакт ' . $counter,
        'slug' => 'contact-' . $counter,
        'draft_content' => [
            'version' => 1,
            'elements' => [
                [
                    'id' => 'form_1',
                    'type' => 'form',
                    'settings' => [
                        'fields' => $fields,
                        'submit_label' => 'Send',
                        'actions' => $actions,
                        'success_message' => 'Ви благодариме!',
                        'honeypot' => true,
                    ],
                ],
            ],
        ],
    ])->publish();
}

it('processes form submissions with validation, optional database persistence, and pluggable actions', function (): void {
    $page = createFormPage([
        ['action' => 'save_to_database', 'settings' => []],
        ['action' => 'redirect', 'settings' => ['url' => '/thank-you']],
    ]);

    $this->get($page->getPublicUrl())->assertOk()->assertSee('fpb-form', false);

    // Invalid submission (missing email)
    $this->post("/_builder/form/{$page->id}/form_1", ['name' => 'Зоран'])
        ->assertSessionHasErrors(['email']);

    expect(FormSubmission::count())->toBe(0);

    // Valid submission
    $response = $this->post("/_builder/form/{$page->id}/form_1", [
        'name' => 'Зоран',
        'email' => 'zbogoevski@gmail.com',
        'message' => 'Здраво!',
    ]);

    $response->assertRedirect('/thank-you');

    $submission = FormSubmission::firstOrFail();
    expect($submission->page_id)->toBe($page->id)
        ->and($submission->element_id)->toBe('form_1')
        ->and($submission->data['name'])->toBe('Зоран')
        ->and($submission->data['email'])->toBe('zbogoevski@gmail.com');

    $log = collect($submission->actions_log)->pluck('action')->toArray();
    expect($log)->toContain('save_to_database')
        ->and($log)->toContain('redirect');
});

it('does not persist submissions when save_to_database is not configured', function (): void {
    $page = createFormPage([
        ['action' => 'redirect', 'settings' => ['url' => '/done']],
    ]);

    $this->post("/_builder/form/{$page->id}/form_1", [
        'name' => 'Ана',
        'email' => 'ana@example.com',
    ])->assertRedirect('/done');

    expect(FormSubmission::count())->toBe(0);
});

it('returns error response when a form action fails', function (): void {
    $page = createFormPage([
        ['action' => 'email', 'settings' => ['to' => 'not-an-email']],
    ]);

    $this->post("/_builder/form/{$page->id}/form_1", [
        'name' => 'Ана',
        'email' => 'ana@example.com',
    ])->assertSessionHasErrors(['fpb_form']);

    $jsonPage = createFormPage([
        ['action' => 'email', 'settings' => ['to' => 'invalid']],
    ]);

    $this->postJson("/_builder/form/{$jsonPage->id}/form_1", [
        'name' => 'Ана',
        'email' => 'ana@example.com',
    ])->assertStatus(422)->assertJson(['success' => false]);
});

it('validates select options, numeric boundaries, and required checkboxes', function (): void {
    $page = createFormPage(
        [['action' => 'save_to_database', 'settings' => []]],
        [
            ['name' => 'color', 'type' => 'select', 'label' => 'Color', 'required' => true, 'options' => 'Red, Green, Blue'],
            ['name' => 'quantity', 'type' => 'number', 'label' => 'Quantity', 'required' => true],
            ['name' => 'terms', 'type' => 'checkbox', 'label' => 'Accept Terms', 'required' => true],
        ],
    );

    // Invalid select option
    $this->post("/_builder/form/{$page->id}/form_1", [
        'color' => 'Purple',
        'quantity' => 5,
        'terms' => 1,
    ])->assertSessionHasErrors(['color']);

    // Numeric > 255 is allowed (no artificial max)
    $this->post("/_builder/form/{$page->id}/form_1", [
        'color' => 'Red',
        'quantity' => 1000,
        'terms' => 1,
    ])->assertRedirect();

    // Unchecked required checkbox fails
    $this->post("/_builder/form/{$page->id}/form_1", [
        'color' => 'Blue',
        'quantity' => 5,
        'terms' => 0,
    ])->assertSessionHasErrors(['terms']);
});

it('blocks SSRF via webhook to private hosts and allows public HTTPS webhooks', function (): void {
    Http::fake(['https://hooks.example.com/*' => Http::response(['ok' => true], 200)]);

    $privatePage = createFormPage([
        ['action' => 'webhook', 'settings' => ['url' => 'https://127.0.0.1/hook']],
    ]);

    $this->post("/_builder/form/{$privatePage->id}/form_1", [
        'name' => 'Ана',
        'email' => 'ana@example.com',
    ])->assertSessionHasErrors(['fpb_form']);

    Http::assertNothingSent();

    $publicPage = createFormPage([
        ['action' => 'save_to_database', 'settings' => []],
        ['action' => 'webhook', 'settings' => ['url' => 'https://hooks.example.com/notify']],
    ]);

    $this->post("/_builder/form/{$publicPage->id}/form_1", [
        'name' => 'Ана',
        'email' => 'ana@example.com',
    ])->assertRedirect();

    Http::assertSentCount(1);
});

it('blocks honeypot spam and rejects unpublished pages or unknown form elements', function (): void {
    $page = createFormPage();

    $response = $this->post("/_builder/form/{$page->id}/form_1", [
        'name' => 'Bot',
        'email' => 'bot@example.com',
        '_hp' => 'spam-filled',
    ]);

    $response->assertRedirect($page->getPublicUrl());
    expect(FormSubmission::count())->toBe(0);

    $draft = Page::create(['title' => 'Draft Page', 'slug' => 'draft-form']);
    $this->post("/_builder/form/{$draft->id}/form_1", ['name' => 'X'])->assertNotFound();

    $this->post("/_builder/form/{$page->id}/unknown_element", ['name' => 'X'])->assertNotFound();
});
