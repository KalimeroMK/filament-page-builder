<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\DynamicTags;

use Illuminate\Database\Eloquent\Model;
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

        // Convert Eloquent models via toArray() so $hidden attributes (e.g. password, remember_token) are never exposed
        $safeData = $record instanceof Model ? $record->toArray() : $record;

        return data_get($safeData, $key, $parameters['fallback'] ?? null);
    }
}
