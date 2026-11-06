<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use KalimeroMK\FilamentPageBuilder\Models\FormSubmission;
use KalimeroMK\FilamentPageBuilder\Models\Page;
use KalimeroMK\FilamentPageBuilder\Models\Popup;
use KalimeroMK\FilamentPageBuilder\Registries\FormActionRegistry;
use KalimeroMK\FilamentPageBuilder\Support\SchemaTreeManipulator;

class FormController extends Controller
{
    public function submit(Request $request, int | string $page, string $elementId, FormActionRegistry $actions): RedirectResponse | JsonResponse
    {
        /** @var class-string<Page> $pageModel */
        $pageModel = config('filament-page-builder.models.page', Page::class);

        /** @var Page $pageRecord */
        $pageRecord = $pageModel::query()->published()->findOrFail($page);

        return $this->processSubmission($request, $pageRecord, $pageRecord->getPublishedSchema(), $elementId, $actions);
    }

    public function submitPopup(Request $request, int | string $popup, string $elementId, FormActionRegistry $actions): RedirectResponse | JsonResponse
    {
        /** @var class-string<Popup> $popupModel */
        $popupModel = config('filament-page-builder.models.popup', Popup::class);

        /** @var Popup $popupRecord */
        $popupRecord = $popupModel::query()->published()->findOrFail($popup);

        /** @var Page|null $hostPage */
        $hostPage = null;
        if ($request->filled('_host_page')) {
            $pageModel = config('filament-page-builder.models.page', Page::class);
            $hostPage = $pageModel::query()->published()->find($request->input('_host_page'));
        }

        return $this->processSubmission($request, $hostPage ?? $popupRecord, $popupRecord->getPublishedSchema(), $elementId, $actions);
    }

    /**
     * @param  Page|Popup  $owner
     * @param  array{version: int, elements: array<int, array<string, mixed>>}  $schema
     */
    protected function processSubmission(Request $request, Page | Popup $owner, array $schema, string $elementId, FormActionRegistry $actions): RedirectResponse | JsonResponse
    {
        $element = SchemaTreeManipulator::findElement($schema['elements'], $elementId);

        if ($element === null || ($element['type'] ?? '') !== 'form') {
            abort(404);
        }

        $settings = is_array($element['settings'] ?? null) ? $element['settings'] : [];
        $errorBag = 'fpb_' . $elementId;

        // Honeypot anti-spam: if the hidden field is filled, silently succeed without storing
        if (! empty($settings['honeypot']) && $request->filled('_hp')) {
            return $this->respondSuccess($request, $owner, $elementId, (string) ($settings['success_message'] ?? 'Thank you!'), null);
        }

        $this->verifyTurnstileIfEnabled($request, $settings, $errorBag);

        $fields = is_array($settings['fields'] ?? null) ? $settings['fields'] : [];
        $rules = [];

        foreach ($fields as $field) {
            if (! is_array($field)) {
                continue;
            }

            $name = (string) ($field['name'] ?? '');
            if ($name === '' || ! preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
                continue;
            }

            if (! $this->isFieldVisible($field, $request)) {
                continue;
            }

            $type = (string) ($field['type'] ?? 'text');
            $required = ! empty($field['required']);

            if ($type === 'checkbox') {
                $rules[$name] = [$required ? 'accepted' : 'boolean'];

                continue;
            }

            $fieldRules = [$required ? 'required' : 'nullable'];

            if ($type === 'email') {
                $fieldRules[] = 'email:rfc';
                $fieldRules[] = 'max:255';
            } elseif ($type === 'number') {
                $fieldRules[] = 'numeric';
            } elseif ($type === 'select') {
                $options = $this->normalizeOptions((string) ($field['options'] ?? ''));
                $fieldRules[] = 'string';
                $fieldRules[] = 'max:255';
                if ($options !== []) {
                    $fieldRules[] = Rule::in($options);
                }
            } else {
                $fieldRules[] = 'string';
                $fieldRules[] = $type === 'textarea' ? 'max:5000' : 'max:255';
            }

            $rules[$name] = $fieldRules;
        }

        $validator = Validator::make($request->all(), $rules);
        $validated = $validator->validateWithBag($errorBag);

        $actionConfigs = is_array($settings['actions'] ?? null) ? $settings['actions'] : [];
        $shouldPersist = collect($actionConfigs)->contains(fn ($a): bool => is_array($a) && ($a['action'] ?? '') === 'save_to_database');

        /** @var class-string<FormSubmission> $submissionModel */
        $submissionModel = config('filament-page-builder.models.form_submission', FormSubmission::class);

        /** @var FormSubmission $submission */
        $submission = new $submissionModel([
            'page_id' => $owner->getKey(),
            'element_id' => $elementId,
            'data' => $validated,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
            'actions_log' => [],
        ]);

        $redirectUrl = null;
        $log = [];
        $failures = [];

        foreach ($actionConfigs as $actionConfig) {
            if (! is_array($actionConfig)) {
                continue;
            }

            $actionName = (string) ($actionConfig['action'] ?? '');
            $action = $actions->get($actionName);

            if ($action === null) {
                continue;
            }

            try {
                $result = $action->handle(
                    is_array($actionConfig['settings'] ?? null) ? $actionConfig['settings'] : [],
                    $validated,
                    $submission,
                    $this->resolveActionPage($owner),
                    ['request' => $request, 'owner' => $owner],
                );
            } catch (\Throwable $e) {
                Log::error('Form action failed', [
                    'action' => $actionName,
                    'owner_id' => $owner->getKey(),
                    'error' => $e->getMessage(),
                ]);

                $result = ['success' => false, 'message' => 'Action failed to execute.'];
            }

            $log[] = ['action' => $actionName, 'result' => $result];

            if (empty($result['success'])) {
                $failures[] = $actionName . ': ' . (string) ($result['message'] ?? 'failed');
            }

            if (! empty($result['redirect'])) {
                $redirectUrl = (string) $result['redirect'];
            }
        }

        if ($shouldPersist) {
            $submission->actions_log = $log;
            $submission->save();
        }

        if ($failures !== []) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some actions failed to complete.',
                    'errors' => $failures,
                ], 422);
            }

            throw ValidationException::withMessages([
                'fpb_form' => ['Some actions failed to complete: ' . implode('; ', $failures)],
            ])->errorBag($errorBag);
        }

        return $this->respondSuccess(
            $request,
            $owner,
            $elementId,
            (string) ($settings['success_message'] ?? 'Thank you! Your submission has been received.'),
            $redirectUrl,
        );
    }

    /**
     * Build a Page instance suitable for form actions from the owning Page or Popup.
     */
    protected function resolveActionPage(Page | Popup $owner): Page
    {
        if ($owner instanceof Page) {
            return $owner;
        }

        $page = new Page();
        $page->title = $owner->title;
        $page->slug = 'popup-' . $owner->getKey();
        $page->exists = true;
        $page->setAttribute($page->getKeyName(), $owner->getKey());

        return $page;
    }

    /**
     * @return array<int, string>
     */
    protected function normalizeOptions(string $options): array
    {
        return array_values(array_filter(array_map('trim', explode(',', $options)), fn (string $o): bool => $o !== ''));
    }

    /**
     * @param  array<string, mixed>  $field
     */
    protected function isFieldVisible(array $field, Request $request): bool
    {
        $visibleIfField = trim((string) ($field['visible_if_field'] ?? ''));
        if ($visibleIfField === '') {
            return true;
        }

        $expected = (string) ($field['visible_if_value'] ?? '');
        $actual = $request->input($visibleIfField);

        if (! is_scalar($actual) && $actual !== null) {
            return false;
        }

        return (string) $actual === $expected;
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    protected function verifyTurnstileIfEnabled(Request $request, array $settings, string $errorBag): void
    {
        if (empty($settings['turnstile'])) {
            return;
        }

        $secret = (string) config('filament-page-builder.forms.turnstile.secret_key', '');
        $siteKey = (string) config('filament-page-builder.forms.turnstile.site_key', '');

        if ($secret === '' || $siteKey === '') {
            throw ValidationException::withMessages([
                'cf-turnstile-response' => ['Turnstile is enabled but not fully configured (missing site or secret key).'],
            ])->errorBag($errorBag);
        }

        $token = (string) $request->input('cf-turnstile-response', '');

        try {
            $response = Http::asForm()->timeout(10)->post(
                'https://challenges.cloudflare.com/turnstile/v0/siteverify',
                ['secret' => $secret, 'response' => $token, 'remoteip' => $request->ip()],
            );
        } catch (\Throwable $e) {
            Log::error('Turnstile verification request failed', ['error' => $e->getMessage()]);

            throw ValidationException::withMessages([
                'cf-turnstile-response' => ['Turnstile verification is temporarily unavailable. Please try again.'],
            ])->errorBag($errorBag);
        }

        if (! $response->json('success')) {
            throw ValidationException::withMessages([
                'cf-turnstile-response' => ['Turnstile verification failed. Please try again.'],
            ])->errorBag($errorBag);
        }
    }

    protected function respondSuccess(Request $request, Page | Popup $owner, string $elementId, string $message, ?string $redirectUrl): RedirectResponse | JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'redirect' => $redirectUrl,
            ]);
        }

        if ($redirectUrl !== null) {
            $target = $redirectUrl;
        } elseif ($owner instanceof Page) {
            $target = $owner->getPublicUrl();
        } else {
            $target = $request->headers->get('referer') ?: '/';
        }

        return redirect()
            ->to($target)
            ->with('fpb_form_success', $message)
            ->with('fpb_form_element', $elementId);
    }
}
