<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\DynamicTags;

use Illuminate\Support\Carbon;
use KalimeroMK\FilamentPageBuilder\Contracts\DynamicTagContract;

class CurrentDateDynamicTag implements DynamicTagContract
{
    public static function getName(): string
    {
        return 'site.current_date';
    }

    public static function getLabel(): string
    {
        return 'Current Date';
    }

    public static function getGroup(): string
    {
        return 'Site';
    }

    public function resolve(array $parameters = [], array $context = []): mixed
    {
        $format = is_string($parameters['key'] ?? null) && $parameters['key'] !== ''
            ? $parameters['key']
            : (string) ($parameters['format'] ?? 'Y-m-d');

        return Carbon::now()->format($format);
    }
}
