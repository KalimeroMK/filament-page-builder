<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\FormActions;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use KalimeroMK\FilamentPageBuilder\Contracts\FormActionContract;
use KalimeroMK\FilamentPageBuilder\Models\FormSubmission;
use KalimeroMK\FilamentPageBuilder\Models\Page;
use KalimeroMK\FilamentPageBuilder\Support\HtmlSanitizer;

class WebhookAction implements FormActionContract
{
    public static function getName(): string
    {
        return 'webhook';
    }

    public static function getLabel(): string
    {
        return 'Send Webhook';
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
            return ['success' => false, 'message' => 'Webhook URL is empty.'];
        }

        $safe = HtmlSanitizer::sanitizeUrl($url, '');
        if ($safe === '' || ! str_starts_with($safe, 'https://') || ! $this->isSafeWebhookUrl($safe)) {
            return ['success' => false, 'message' => 'Webhook URL is not allowed.'];
        }

        try {
            $response = Http::timeout(10)->withoutRedirecting()->post($safe, [
                'page' => $page->title,
                'page_slug' => $page->slug,
                'submission_id' => $submission->id,
                'data' => $formData,
            ]);

            if (! $response->successful()) {
                return [
                    'success' => false,
                    'message' => 'Webhook returned status ' . $response->status(),
                ];
            }

            return ['success' => true];
        } catch (\Throwable $e) {
            Log::error('Webhook action failed', ['error' => $e->getMessage()]);

            return ['success' => false, 'message' => 'Webhook request failed.'];
        }
    }

    protected function isSafeWebhookUrl(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        if ($host === '') {
            return false;
        }

        $allowedHosts = array_map('strtolower', (array) config('filament-page-builder.forms.webhook_allowed_hosts', []));
        if ($allowedHosts !== [] && ! in_array($host, $allowedHosts, true)) {
            return false;
        }

        if ($host === 'localhost' || str_ends_with($host, '.local') || str_ends_with($host, '.internal')) {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return $this->isPublicIp($host);
        }

        $resolved = gethostbyname($host);

        return $resolved === $host || $this->isPublicIp($resolved);
    }

    protected function isPublicIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
        ) !== false;
    }
}
