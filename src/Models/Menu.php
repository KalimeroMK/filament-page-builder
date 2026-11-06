<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property array<int, array<string, mixed>>|null $items
 */
class Menu extends Model
{
    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::saving(function (Menu $menu): void {
            if ($menu->isDirty('items')) {
                $menu->items = static::normalizeItems(is_array($menu->items) ? $menu->items : []);
            }
        });
    }

    public function getTable(): string
    {
        return (string) config('filament-page-builder.tables.menus', 'fpb_menus');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'items' => 'array',
        ];
    }

    /**
     * @param  array<mixed>  $items
     * @return array<int, array{label: string, url: string, target: string, children: array<int, array{label: string, url: string, target: string}>}>
     */
    public static function normalizeItems(array $items): array
    {
        $normalized = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $label = trim((string) ($item['label'] ?? ''));
            $url = \KalimeroMK\FilamentPageBuilder\Support\HtmlSanitizer::sanitizeUrl(trim((string) ($item['url'] ?? '')), '');

            if ($label === '' || $url === '') {
                continue;
            }

            $children = [];
            foreach ((array) ($item['children'] ?? []) as $child) {
                if (! is_array($child)) {
                    continue;
                }
                $childLabel = trim((string) ($child['label'] ?? ''));
                $childUrl = \KalimeroMK\FilamentPageBuilder\Support\HtmlSanitizer::sanitizeUrl(trim((string) ($child['url'] ?? '')), '');
                if ($childLabel === '' || $childUrl === '') {
                    continue;
                }
                $children[] = [
                    'label' => $childLabel,
                    'url' => $childUrl,
                    'target' => ($child['target'] ?? '_self') === '_blank' ? '_blank' : '_self',
                ];
            }

            $normalized[] = [
                'label' => $label,
                'url' => $url,
                'target' => ($item['target'] ?? '_self') === '_blank' ? '_blank' : '_self',
                'children' => $children,
            ];
        }

        return $normalized;
    }
}
