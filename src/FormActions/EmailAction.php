<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\FormActions;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use KalimeroMK\FilamentPageBuilder\Contracts\FormActionContract;
use KalimeroMK\FilamentPageBuilder\Models\FormSubmission;
use KalimeroMK\FilamentPageBuilder\Models\Page;

class EmailAction implements FormActionContract
{
    public static function getName(): string
    {
        return 'email';
    }

    public static function getLabel(): string
    {
        return 'Send Email Notification';
    }

    public function handle(
        array $actionSettings,
        array $formData,
        FormSubmission $submission,
        Page $page,
        array $context = [],
    ): array {
        $to = trim((string) ($actionSettings['to'] ?? ''));
        $subject = trim((string) ($actionSettings['subject'] ?? ('New form submission: ' . $page->title))) ?: 'New form submission';

        if ($to === '' || ! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid recipient email.'];
        }

        $body = "New submission on page: {$page->title}\n\n";
        foreach ($formData as $key => $value) {
            if (str_starts_with($key, '_')) {
                continue;
            }
            $body .= $key . ': ' . (is_scalar($value) ? $value : json_encode($value)) . "\n";
        }

        try {
            Mail::raw($body, function ($message) use ($to, $subject): void {
                $message->to($to)->subject($subject);
            });

            return ['success' => true];
        } catch (\Throwable $e) {
            Log::error('Email action failed', ['error' => $e->getMessage()]);

            return ['success' => false, 'message' => 'Email could not be sent.'];
        }
    }
}
