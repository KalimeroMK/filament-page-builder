<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Rendering;

use KalimeroMK\FilamentPageBuilder\Contracts\WidgetContract;
use Stringable;

class CssGenerator
{
    /**
     * Generate base structural CSS for the canvas and widgets using CSS variables
     * and @container queries so responsive preview works even without an iframe.
     */
    public function baseStyles(): string
    {
        $globalCss = app(\KalimeroMK\FilamentPageBuilder\Services\SiteStyleService::class)->toCss();

        return $globalCss . "\n" . <<<'CSS'
.fpb-canvas-root {
    container-type: inline-size;
    container-name: fpb-canvas;
    width: 100%;
    box-sizing: border-box;
    font-family: var(--fpb-global-font-family, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif);
    color: var(--fpb-global-text, #111827);
    font-size: var(--fpb-global-body-size, 16px);
}
.fpb-canvas-root h1 { font-size: var(--fpb-global-h1-size, 48px); }
.fpb-canvas-root h2 { font-size: var(--fpb-global-h2-size, 36px); }
.fpb-canvas-root h3 { font-size: var(--fpb-global-h3-size, 28px); }
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
    color: var(--fpb-color, var(--fpb-global-text, #111827));
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
    background-color: var(--fpb-btn-bg, var(--fpb-global-primary, #2563eb));
    color: var(--fpb-btn-color, #ffffff);
    border: 1px solid transparent;
    border-radius: var(--fpb-radius, 8px);
    font-size: var(--fpb-font-size, 16px);
    font-weight: 600;
    text-decoration: none;
    transition: opacity 0.15s ease-in-out;
}
.fpb-button--secondary {
    background-color: var(--fpb-btn-bg, #475569);
    color: var(--fpb-btn-color, #ffffff);
}
.fpb-button--outline {
    background-color: var(--fpb-btn-bg, transparent);
    color: var(--fpb-btn-color, #2563eb);
    border-color: currentColor;
}
.fpb-button--dark {
    background-color: var(--fpb-btn-bg, #0f172a);
    color: var(--fpb-btn-color, #ffffff);
}
.fpb-button:hover {
    opacity: 0.92;
}
.fpb-image-wrap {
    text-align: var(--fpb-text-align, center);
}
.fpb-image {
    max-width: var(--fpb-max-width, 100%);
    width: var(--fpb-width, auto);
    height: var(--fpb-height, auto);
    object-fit: var(--fpb-object-fit, cover);
    border-radius: var(--fpb-radius, 0px);
    display: inline-block;
}
.fpb-spacer {
    height: var(--fpb-space, 32px);
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
}
.fpb-spacer-line {
    width: var(--fpb-divider-width, 100%);
    border-top-width: var(--fpb-divider-weight, 1px);
    border-top-style: var(--fpb-divider-style, solid);
    border-top-color: var(--fpb-divider-color, #e5e7eb);
}
.fpb-icon-wrap {
    text-align: var(--fpb-text-align, center);
}
.fpb-icon-box {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: var(--fpb-icon-color, #2563eb);
    background-color: var(--fpb-icon-bg, transparent);
    width: calc(var(--fpb-icon-size, 40px) * 1.6);
    height: calc(var(--fpb-icon-size, 40px) * 1.6);
    border-radius: var(--fpb-radius, 50%);
}
.fpb-icon-box svg {
    width: var(--fpb-icon-size, 40px);
    height: var(--fpb-icon-size, 40px);
}
.fpb-video-wrap {
    position: relative;
    width: 100%;
    overflow: hidden;
    border-radius: var(--fpb-radius, 8px);
    aspect-ratio: var(--fpb-aspect-ratio, 16 / 9);
    background: #0f172a;
}
.fpb-video-wrap iframe,
.fpb-video-wrap video {
    width: 100%;
    height: 100%;
    border: 0;
    display: block;
}
.fpb-gallery-grid {
    display: grid;
    grid-template-columns: repeat(var(--fpb-gallery-cols, 3), minmax(0, 1fr));
    gap: var(--fpb-gap, 16px);
}
.fpb-gallery-img {
    width: 100%;
    height: auto;
    border-radius: var(--fpb-radius, 8px);
    object-fit: cover;
    display: block;
}
.fpb-alert {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    padding: 14px 18px;
    border-radius: var(--fpb-radius, 8px);
    border: 1px solid transparent;
}
.fpb-alert--info { background: #eff6ff; color: #1e40af; border-color: #bfdbfe; }
.fpb-alert--success { background: #f0fdf4; color: #166534; border-color: #bbf7d0; }
.fpb-alert--warning { background: #fffbeb; color: #92400e; border-color: #fde68a; }
.fpb-alert--danger { background: #fef2f2; color: #991b1b; border-color: #fecaca; }
.fpb-alert-title { font-weight: 700; margin-bottom: 4px; }
.fpb-alert-close { background: transparent; border: 0; font-size: 18px; line-height: 1; cursor: pointer; color: inherit; }
.fpb-accordion {
    border: 1px solid var(--fpb-border-color, #e2e8f0);
    border-radius: var(--fpb-radius, 8px);
    overflow: hidden;
}
.fpb-accordion-item + .fpb-accordion-item {
    border-top: 1px solid var(--fpb-border-color, #e2e8f0);
}
.fpb-accordion-trigger {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 18px;
    background: #ffffff;
    color: var(--fpb-title-color, #0f172a);
    font-weight: 600;
    font-size: 15px;
    border: 0;
    cursor: pointer;
    text-align: left;
}
.fpb-accordion-content {
    padding: 0 18px 16px 18px;
    color: #475569;
    line-height: 1.6;
}
.fpb-tabs-nav {
    display: flex;
    gap: 8px;
    border-bottom: 1px solid #e2e8f0;
    margin-bottom: 14px;
}
.fpb-tabs-btn {
    padding: 10px 16px;
    background: transparent;
    border: 0;
    border-bottom: 2px solid transparent;
    font-weight: 600;
    color: #64748b;
    cursor: pointer;
}
.fpb-tabs-btn.is-active {
    color: var(--fpb-active-color, #2563eb);
    border-bottom-color: var(--fpb-active-color, #2563eb);
}
.fpb-flipbox:hover .fpb-flipbox-inner {
    transform: rotateY(180deg);
}
.fpb-loop--grid .fpb-loop-items {
    display: grid;
    grid-template-columns: repeat(var(--fpb-loop-cols, 3), minmax(0, 1fr));
    gap: var(--fpb-gap, 20px);
}
.fpb-loop--list .fpb-loop-items {
    display: flex;
    flex-direction: column;
    gap: var(--fpb-gap, 20px);
}
.fpb-loop-item {
    min-width: 0;
}
.fpb-testimonial {
    background-color: var(--fpb-bg, #f8fafc);
    color: var(--fpb-color, #1e293b);
    border-radius: var(--fpb-radius, 12px);
    text-align: var(--fpb-text-align, left);
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
            $cssVar = $definition['var'] ?? '';

            if (! preg_match('/^--[a-zA-Z0-9_-]+$/', $cssVar)) {
                continue;
            }

            $unit = $definition['unit'] ?? '';

            $desktopVal = $this->formatCssValue($this->extractResponsiveValue($settings, $settingKey, 'desktop'), $unit);
            $tabletVal = $this->formatCssValue($this->extractResponsiveValue($settings, $settingKey, 'tablet'), $unit);
            $mobileVal = $this->formatCssValue($this->extractResponsiveValue($settings, $settingKey, 'mobile'), $unit);

            if ($desktopVal !== '') {
                $desktopDecls[] = "{$cssVar}: {$desktopVal};";
            }

            if ($tabletVal !== '') {
                $tabletDecls[] = "{$cssVar}: {$tabletVal};";
            }

            if ($mobileVal !== '') {
                $mobileDecls[] = "{$cssVar}: {$mobileVal};";
            }
        }

        $rules = [];

        if ($desktopDecls !== []) {
            $rules[] = "{$selector} { " . implode(' ', $desktopDecls) . ' }';
        }

        $customCss = $this->sanitizeCustomCss((string) ($settings['custom_css'] ?? ''));
        if ($customCss !== '') {
            $rules[] = "{$selector} { {$customCss} }";
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

    protected function sanitizeCustomCss(string $css): string
    {
        $css = trim($css);

        if ($css === '') {
            return '';
        }

        // Reject the whole declaration if it contains dangerous constructs (no reconstruction by deletion)
        if (preg_match('/[{}<>\\\\]|\/\*|\*\/|@import|url\s*\(|expression\s*\(|javascript\s*:/i', $css)) {
            return '';
        }

        return $css;
    }

    protected function formatCssValue(mixed $value, string $unit): string
    {
        if ($value === null || (! is_scalar($value) && ! $value instanceof Stringable)) {
            return '';
        }

        $str = trim((string) $value);

        if ($str === '') {
            return '';
        }

        // Strip characters and sequences that could break out of a CSS declaration or load external resources
        $str = preg_replace('/[;{}<>\\\\]|\/\*|\*\/|url\s*\(/i', '', $str) ?? '';
        $str = trim($str);

        if ($str === '') {
            return '';
        }

        if ($unit !== '' && is_numeric($str)) {
            return $str . $unit;
        }

        return $str;
    }
}
