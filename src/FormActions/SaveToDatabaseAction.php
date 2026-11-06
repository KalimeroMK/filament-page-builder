<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\FormActions;

use KalimeroMK\FilamentPageBuilder\Contracts\FormActionContract;
use KalimeroMK\FilamentPageBuilder\Models\FormSubmission;
use KalimeroMK\FilamentPageBuilder\Models\Page;

class SaveToDatabaseAction implements FormActionContract
{
    public static function getName(): string
    {
        return 'save_to_database';
    }

    public static function getLabel(): string
    {
        return 'Save Submission to Database';
    }

    public function handle(
        array $actionSettings,
        array $formData,
        FormSubmission $submission,
        Page $page,
        array $context = [],
    ): array {
        // Submission is always persisted by the controller before actions run.
        return ['success' => true, 'message' => 'Submission stored in fpb_form_submissions.'];
    }
}
