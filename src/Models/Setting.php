<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $key
 * @property array<string, mixed>|null $values
 */
class Setting extends Model
{
    protected $guarded = ['id'];

    public function getTable(): string
    {
        return (string) config('filament-page-builder.tables.settings', 'fpb_settings');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'values' => 'array',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function getGroup(string $key, array $defaults = []): array
    {
        $record = static::query()->where('key', $key)->first();

        $values = is_array($record?->values) ? $record->values : [];

        return array_merge($defaults, $values);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public static function setGroup(string $key, array $values): self
    {
        /** @var self $record */
        $record = static::query()->updateOrCreate(['key' => $key], ['values' => $values]);

        return $record;
    }
}
