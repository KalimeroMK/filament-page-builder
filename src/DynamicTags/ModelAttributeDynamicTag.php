<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\DynamicTags;

use KalimeroMK\FilamentPageBuilder\Contracts\DynamicTagContract;

class ModelAttributeDynamicTag implements DynamicTagContract
{
    public static function getName(): string
    {
        return 'model.attribute';
    }

    public static function getLabel(): string
    {
        return 'Eloquent Model Attribute';
    }

    public static function getGroup(): string
    {
        return 'Data';
    }

    public function resolve(array $parameters = [], array $context = []): mixed
    {
        $key = (string) ($parameters['key'] ?? $parameters['attribute'] ?? '');

        if ($key === '') {
            return $parameters['fallback'] ?? null;
        }

        $record = $context['record'] ?? $context['model'] ?? $context['page'] ?? null;

        if ($record === null) {
            return $parameters['fallback'] ?? null;
        }

        return data_get($record, $key, $parameters['fallback'] ?? null);
    }
}
