<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\FormActions;

use KalimeroMK\FilamentPageBuilder\Contracts\FormActionContract;
use KalimeroMK\FilamentPageBuilder\Models\FormSubmission;
use KalimeroMK\FilamentPageBuilder\Models\Page;
use KalimeroMK\FilamentPageBuilder\Support\HtmlSanitizer;

class RedirectAction implements FormActionContract
{
    public static function getName(): string
    {
        return 'redirect';
    }

    public static function getLabel(): string
    {
        return 'Redirect to URL';
    }

    public function handle(
        array $actionSettings,
        array $formData,
        FormSubmission $submission,
        Page $page,
        array $context = [],
    ): array {
        $url = trim((string) ($actionSettings['url'] ?? ''));

        if ($url === '') {
            return ['success' => false, 'message' => 'Redirect URL is empty.'];
        }

        $safe = HtmlSanitizer::sanitizeUrl($url, '');

        if ($safe === '') {
            return ['success' => false, 'message' => 'Invalid redirect URL.'];
        }

        $allowExternal = (bool) config('filament-page-builder.forms.allow_external_redirects', false);
        $isRelative = str_starts_with($safe, '/') || str_starts_with($safe, '#') || str_starts_with($safe, '?');

        if (! $isRelative && ! $allowExternal) {
            return ['success' => false, 'message' => 'External redirect URLs are not allowed.'];
        }

        return ['success' => true, 'redirect' => $safe];
    }
}
