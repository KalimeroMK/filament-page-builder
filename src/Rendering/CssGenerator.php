<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Rendering;

use KalimeroMK\FilamentPageBuilder\Contracts\WidgetContract;

class CssGenerator
{
    /**
     * Generate base structural CSS for the canvas and widgets using CSS variables
     * and @container queries so responsive preview works even without an iframe.
     */
    public function baseStyles(): string
    {
        return <<<'CSS'
.fpb-canvas-root {
    container-type: inline-size;
    container-name: fpb-canvas;
    width: 100%;
    box-sizing: border-box;
}
.fpb-canvas-root *, .fpb-canvas-root *::before, .fpb-canvas-root *::after {
    box-sizing: border-box;
}
.fpb-element {
    margin-top: var(--fpb-margin-top, 0px);
    margin-bottom: var(--fpb-margin-bottom, 0px);
    padding: var(--fpb-padding, 0px);
}
.fpb-container {
    display: flex;
    flex-direction: var(--fpb-direction, column);
    flex-wrap: var(--fpb-wrap, nowrap);
    gap: var(--fpb-gap, 16px);
    align-items: var(--fpb-align, stretch);
    justify-content: var(--fpb-justify, flex-start);
    background-color: var(--fpb-bg, transparent);
    border-radius: var(--fpb-radius, 0px);
    max-width: var(--fpb-max-width, 100%);
    width: 100%;
    margin-left: auto;
    margin-right: auto;
}
.fpb-heading {
    color: var(--fpb-color, #111827);
    font-size: var(--fpb-font-size, 32px);
    font-weight: var(--fpb-font-weight, 700);
    text-align: var(--fpb-text-align, left);
    line-height: 1.25;
}
.fpb-text {
    color: var(--fpb-color, #374151);
    font-size: var(--fpb-font-size, 16px);
    text-align: var(--fpb-text-align, left);
    line-height: 1.65;
}
.fpb-button-wrap {
    text-align: var(--fpb-text-align, left);
}
.fpb-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 24px;
    background-color: var(--fpb-btn-bg, #2563eb);
    color: var(--fpb-btn-color, #ffffff);
    border-radius: var(--fpb-radius, 8px);
    font-size: var(--fpb-font-size, 16px);
    font-weight: 600;
    text-decoration: none;
    transition: opacity 0.15s ease-in-out;
}
.fpb-button:hover {
    opacity: 0.92;
}
CSS;
    }

    /**
     * Generate per-element CSS rules (desktop + tablet + mobile) from widget getCssMap().
     *
     * @param  array<string, mixed>  $settings
     */
    public function generateForElement(string $elementId, WidgetContract $widget, array $settings): string
    {
        $safeId = preg_replace('/[^a-zA-Z0-9_-]/', '', $elementId) ?: 'el';
        $selector = ".fpb-el-{$safeId}";

        $desktopDecls = [];
        $tabletDecls = [];
        $mobileDecls = [];

        foreach ($widget->getCssMap() as $settingKey => $definition) {
            $cssVar = $definition['var'];
            $unit = $definition['unit'] ?? '';

            $desktopVal = $this->extractResponsiveValue($settings, $settingKey, 'desktop');
            $tabletVal = $this->extractResponsiveValue($settings, $settingKey, 'tablet');
            $mobileVal = $this->extractResponsiveValue($settings, $settingKey, 'mobile');

            if ($desktopVal !== null && $desktopVal !== '') {
                $desktopDecls[] = "{$cssVar}: " . $this->formatCssValue($desktopVal, $unit) . ';';
            }

            if ($tabletVal !== null && $tabletVal !== '') {
                $tabletDecls[] = "{$cssVar}: " . $this->formatCssValue($tabletVal, $unit) . ';';
            }

            if ($mobileVal !== null && $mobileVal !== '') {
                $mobileDecls[] = "{$cssVar}: " . $this->formatCssValue($mobileVal, $unit) . ';';
            }
        }

        $rules = [];

        if ($desktopDecls !== []) {
            $rules[] = "{$selector} { " . implode(' ', $desktopDecls) . ' }';
        }

        $tabletBreakpoint = (int) config('filament-page-builder.breakpoints.tablet', 1024);
        $mobileBreakpoint = (int) config('filament-page-builder.breakpoints.mobile', 767);

        if ($tabletDecls !== []) {
            $declStr = implode(' ', $tabletDecls);
            $rules[] = "@container fpb-canvas (max-width: {$tabletBreakpoint}px) { {$selector} { {$declStr} } }";
            $rules[] = "@media (max-width: {$tabletBreakpoint}px) { {$selector} { {$declStr} } }";
        }

        if ($mobileDecls !== []) {
            $declStr = implode(' ', $mobileDecls);
            $rules[] = "@container fpb-canvas (max-width: {$mobileBreakpoint}px) { {$selector} { {$declStr} } }";
            $rules[] = "@media (max-width: {$mobileBreakpoint}px) { {$selector} { {$declStr} } }";
        }

        return implode("\n", $rules);
    }

    /**
     * Supports both flat responsive keys (`padding`, `padding_tablet`, `padding_mobile`)
     * and nested responsive objects (`padding: { desktop: 40, mobile: 16 }`).
     *
     * @param  array<string, mixed>  $settings
     */
    protected function extractResponsiveValue(array $settings, string $key, string $breakpoint): mixed
    {
        if ($breakpoint === 'desktop') {
            $raw = $settings[$key] ?? null;

            if (is_array($raw)) {
                return $raw['desktop'] ?? null;
            }

            return $raw;
        }

        $flatKey = "{$key}_{$breakpoint}";

        if (array_key_exists($flatKey, $settings) && $settings[$flatKey] !== null && $settings[$flatKey] !== '') {
            return $settings[$flatKey];
        }

        if (isset($settings[$key]) && is_array($settings[$key])) {
            return $settings[$key][$breakpoint] ?? null;
        }

        return null;
    }

    protected function formatCssValue(mixed $value, string $unit): string
    {
        $str = trim((string) $value);

        // Strip characters that could break out of a CSS declaration
        $str = str_replace([';', '{', '}', '<', '>'], '', $str);

        if ($unit !== '' && is_numeric($str)) {
            return $str . $unit;
        }

        return $str;
    }
}
