<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use KalimeroMK\FilamentPageBuilder\Http\Controllers\PageController;
use KalimeroMK\FilamentPageBuilder\Http\Controllers\PagePreviewController;

if (config('filament-page-builder.routing.enabled', true)) {
    $previewPrefix = trim((string) config('filament-page-builder.routing.preview_prefix', '_builder/preview'), '/');
    $previewMiddleware = (array) config('filament-page-builder.routing.preview_middleware', ['web']);

    Route::middleware($previewMiddleware)
        ->prefix($previewPrefix)
        ->group(function (): void {
            Route::get('/{page}', [PagePreviewController::class, 'show'])
                ->name('filament-page-builder.preview');
        });

    $publicPrefix = trim((string) config('filament-page-builder.routing.prefix', 'pages'), '/');
    $publicMiddleware = (array) config('filament-page-builder.routing.middleware', ['web']);

    Route::middleware($publicMiddleware)
        ->prefix($publicPrefix)
        ->group(function (): void {
            Route::get('/{slug}', [PageController::class, 'show'])
                ->where('slug', '[A-Za-z0-9\-_\/]+')
                ->name('filament-page-builder.show');
        });

    Route::middleware($publicMiddleware)->group(function (): void {
        $throttle = (string) config('filament-page-builder.forms.rate_limit', '10,1');

        Route::post('/_builder/form/{page}/{elementId}', [\KalimeroMK\FilamentPageBuilder\Http\Controllers\FormController::class, 'submit'])
            ->middleware('throttle:' . $throttle)
            ->name('filament-page-builder.form.submit');

        Route::post('/_builder/popup-form/{popup}/{elementId}', [\KalimeroMK\FilamentPageBuilder\Http\Controllers\FormController::class, 'submitPopup'])
            ->middleware('throttle:' . $throttle)
            ->name('filament-page-builder.popup-form.submit');

        Route::get('/_builder/loop/{page}/{elementId}', [\KalimeroMK\FilamentPageBuilder\Http\Controllers\LoopController::class, 'load'])
            ->name('filament-page-builder.loop.load');
    });

    Route::fallback([\KalimeroMK\FilamentPageBuilder\Http\Controllers\NotFoundController::class, 'show'])
        ->middleware($publicMiddleware);
}
