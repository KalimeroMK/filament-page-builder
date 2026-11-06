<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Support;

use Illuminate\Support\Facades\Gate;

class PageBuilderAuthorizer
{
    /**
     * Check builder area access via the `fpb.access_builder` Gate.
     * Defaults to allowed when no custom callback is configured.
     */
    public static function can(string $area = 'builder'): bool
    {
        if (! Gate::has('fpb.access_builder')) {
            return (bool) config('filament-page-builder.authorization.default_allow', true);
        }

        return Gate::allows('fpb.access_builder', [$area]);
    }
}
