<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use KalimeroMK\FilamentPageBuilder\Models\FormSubmission;
use KalimeroMK\FilamentPageBuilder\Models\Page;
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

        $schema = $pageRecord->getPublishedSchema();
        $element = SchemaTreeManipulator::findElement($schema['elements'], $elementId);

        if ($element === null || ($element['type'] ?? '') !== 'form') {
            abort(404);
        }

        $settings = is_array($element['settings'] ?? null) ? $element['settings'] : [];

        // Honeypot anti-spam: if the hidden field is filled, silently succeed without storing
        if (! empty($settings['honeypot']) && $request->filled('_hp')) {
            return $this->respondSuccess($request, $pageRecord, $elementId, (string) ($settings['success_message'] ?? 'Thank you!'), null);
        }

        $rules = [];
        $fields = is_array($settings['fields'] ?? null) ? $settings['fields'] : [];

        foreach ($fields as $field) {
            if (! is_array($field)) {
                continue;
            }

            $name = (string) ($field['name'] ?? '');
            if ($name === '' || ! preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
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
        $validated = $validator->validate();

        $actionConfigs = is_array($settings['actions'] ?? null) ? $settings['actions'] : [];
        $shouldPersist = collect($actionConfigs)->contains(fn ($a): bool => is_array($a) && ($a['action'] ?? '') === 'save_to_database');

        /** @var class-string<FormSubmission> $submissionModel */
        $submissionModel = config('filament-page-builder.models.form_submission', FormSubmission::class);

        /** @var FormSubmission $submission */
        $submission = new $submissionModel([
            'page_id' => $pageRecord->getKey(),
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
                    $pageRecord,
                    ['request' => $request],
                );
            } catch (\Throwable $e) {
                Log::error('Form action failed', [
                    'action' => $actionName,
                    'page_id' => $pageRecord->getKey(),
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
            ]);
        }

        return $this->respondSuccess(
            $request,
            $pageRecord,
            $elementId,
            (string) ($settings['success_message'] ?? 'Thank you! Your submission has been received.'),
            $redirectUrl,
        );
    }

    /**
     * @return array<int, string>
     */
    protected function normalizeOptions(string $options): array
    {
        return array_values(array_filter(array_map('trim', explode(',', $options)), fn (string $o): bool => $o !== ''));
    }

    protected function respondSuccess(Request $request, Page $page, string $elementId, string $message, ?string $redirectUrl): RedirectResponse | JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'redirect' => $redirectUrl,
            ]);
        }

        $target = $redirectUrl ?? $page->getPublicUrl();

        return redirect()
            ->to($target)
            ->with('fpb_form_success', $message)
            ->with('fpb_form_element', $elementId);
    }
}
