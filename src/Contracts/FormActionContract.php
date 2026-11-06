<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Contracts;

use KalimeroMK\FilamentPageBuilder\Models\FormSubmission;
use KalimeroMK\FilamentPageBuilder\Models\Page;

interface FormActionContract
{
    public static function getName(): string;

    public static function getLabel(): string;

    /**
     * Execute the form action.
     *
     * @param  array<string, mixed>  $actionSettings
     * @param  array<string, mixed>  $formData
     * @param  array<string, mixed>  $context
     * @return array{success: bool, message?: string, redirect?: string|null}
     */
    public function handle(
        array $actionSettings,
        array $formData,
        FormSubmission $submission,
        Page $page,
        array $context = [],
    ): array;
}
