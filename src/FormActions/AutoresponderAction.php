<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\FormActions;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use KalimeroMK\FilamentPageBuilder\Contracts\FormActionContract;
use KalimeroMK\FilamentPageBuilder\Models\FormSubmission;
use KalimeroMK\FilamentPageBuilder\Models\Page;

class AutoresponderAction implements FormActionContract
{
    public static function getName(): string
    {
        return 'autoresponder';
    }

    public static function getLabel(): string
    {
        return 'Autoresponder (email to visitor)';
    }

    public function handle(
        array $actionSettings,
        array $formData,
        FormSubmission $submission,
        Page $page,
        array $context = [],
    ): array {
        $fieldName = trim((string) ($actionSettings['to_field'] ?? 'email')) ?: 'email';
        $to = trim((string) ($formData[$fieldName] ?? ''));

        if ($to === '' || ! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Visitor email field is missing or invalid.'];
        }

        $subject = trim((string) ($actionSettings['subject'] ?? 'Thank you for contacting us')) ?: 'Thank you for contacting us';
        $messageBody = trim((string) ($actionSettings['message'] ?? 'We received your message and will get back to you soon.')) ?: 'Thank you!';

        try {
            Mail::raw($messageBody, function ($message) use ($to, $subject): void {
                $message->to($to)->subject($subject);
            });

            return ['success' => true];
        } catch (\Throwable $e) {
            Log::error('Autoresponder failed', ['error' => $e->getMessage()]);

            return ['success' => false, 'message' => 'Autoresponder could not be sent.'];
        }
    }
}
