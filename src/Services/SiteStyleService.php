<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Services;

use KalimeroMK\FilamentPageBuilder\Models\Setting;

class SiteStyleService
{
    public const SETTING_KEY = 'site_style';

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'primary_color' => '#2563eb',
            'secondary_color' => '#0f172a',
            'text_color' => '#1e293b',
            'accent_color' => '#f59e0b',
            'body_font_size' => 16,
            'h1_font_size' => 48,
            'h2_font_size' => 36,
            'h3_font_size' => 28,
            'font_family' => 'system-ui, -apple-system, "Segoe UI", Roboto, sans-serif',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function values(): array
    {
        return Setting::getGroup(self::SETTING_KEY, self::defaults());
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function save(array $values): void
    {
        Setting::setGroup(self::SETTING_KEY, array_merge($this->values(), $values));
    }

    /**
     * Generate a `:root` CSS block with global design tokens used across the site.
     */
    public function toCss(): string
    {
        $v = $this->values();
        $declarations = [];

        $colorKeys = [
            'primary_color' => '--fpb-global-primary',
            'secondary_color' => '--fpb-global-secondary',
            'text_color' => '--fpb-global-text',
            'accent_color' => '--fpb-global-accent',
        ];

        foreach ($colorKeys as $key => $var) {
            $color = $this->sanitizeColor($v[$key] ?? null);
            if ($color !== null) {
                $declarations[] = "{$var}: {$color};";
            }
        }

        foreach (['body_font_size', 'h1_font_size', 'h2_font_size', 'h3_font_size'] as $key) {
            if (isset($v[$key]) && is_numeric($v[$key])) {
                $size = max(8, min(200, (int) $v[$key]));
                $var = '--fpb-global-' . str_replace('_font_size', '-size', $key);
                $declarations[] = "{$var}: {$size}px;";
            }
        }

        $fontFamily = $this->sanitizeFontFamily((string) ($v['font_family'] ?? ''));
        if ($fontFamily !== '') {
            $declarations[] = "--fpb-global-font-family: {$fontFamily};";
        }

        if ($declarations === []) {
            return '';
        }

        return ':root { ' . implode(' ', $declarations) . ' }';
    }

    protected function sanitizeColor(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $color = trim((string) $value);

        if (preg_match('/^#(?:[0-9a-fA-F]{3,4}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $color)) {
            return $color;
        }

        if (preg_match('/^(?:rgb|hsl)a?\(\s*[0-9.,%\s]+\)$/', $color)) {
            return $color;
        }

        if (preg_match('/^[a-zA-Z]+$/', $color)) {
            return $color;
        }

        return null;
    }

    protected function sanitizeFontFamily(string $value): string
    {
        $value = trim($value);

        if ($value === '' || strlen($value) > 200) {
            return '';
        }

        // Only allow font names, quotes, spaces, commas, hyphens and parentheses-free generic families
        if (! preg_match('/^[a-zA-Z0-9\s,"\-\']+$/', $value)) {
            return '';
        }

        return $value;
    }
}
