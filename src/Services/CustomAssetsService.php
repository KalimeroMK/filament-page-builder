<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Services;

use KalimeroMK\FilamentPageBuilder\Models\Setting;

class CustomAssetsService
{
    /**
     * @return array<int, array{family: string, css_url: string}>
     */
    public function customFonts(): array
    {
        $fonts = Setting::getGroup('custom_fonts', []);

        return array_values(array_filter($fonts, fn ($f): bool => is_array($f) && ! empty($f['family']) && ! empty($f['css_url'])));
    }

    /**
     * @param  array<int, array<string, mixed>>  $fonts
     */
    public function saveFonts(array $fonts): void
    {
        $normalized = [];

        foreach ($fonts as $font) {
            if (! is_array($font)) {
                continue;
            }

            $family = trim((string) ($font['family'] ?? ''));
            $url = trim((string) ($font['css_url'] ?? ''));

            if ($family === '' || ! preg_match('/^[a-zA-Z0-9\s\-]+$/', $family)) {
                continue;
            }

            if (! str_starts_with($url, 'https://') || filter_var($url, FILTER_VALIDATE_URL) === false) {
                continue;
            }

            $normalized[] = ['family' => $family, 'css_url' => $url];
        }

        Setting::setGroup('custom_fonts', $normalized);
    }

    public function fontsToCss(): string
    {
        $rules = [];

        foreach ($this->customFonts() as $font) {
            $family = $font['family'];
            $url = $font['css_url'];

            if (! str_starts_with($url, 'https://')) {
                continue;
            }

            if (str_ends_with(strtolower($url), '.css')) {
                // Stylesheet containing @font-face declarations
                $rules[] = "@import url(\"{$url}\");";
            } else {
                $rules[] = "@font-face { font-family: \"{$family}\"; src: url(\"{$url}\"); font-display: swap; }";
            }
        }

        return implode("\n", $rules);
    }

    /**
     * @return array<string, string>
     */
    public function customIcons(): array
    {
        $icons = Setting::getGroup('custom_icons', []);

        $result = [];
        foreach ($icons as $icon) {
            if (! is_array($icon) || empty($icon['name']) || empty($icon['svg'])) {
                continue;
            }

            $sanitized = $this->sanitizeSvg((string) $icon['svg']);
            if ($sanitized !== '') {
                $result[(string) $icon['name']] = $sanitized;
            }
        }

        return $result;
    }

    /**
     * @return array<int, string>
     */
    public function customFontFamilies(): array
    {
        return array_map(fn (array $font): string => $font['family'], $this->customFonts());
    }

    public function sanitizeSvg(string $svg): string
    {
        $svg = trim($svg);

        if ($svg === '' || ! str_starts_with(strtolower($svg), '<svg')) {
            return '';
        }

        if (! class_exists(\DOMDocument::class)) {
            return '';
        }

        $previousErrors = libxml_use_internal_errors(true);

        $dom = new \DOMDocument();
        $loaded = $dom->loadXML($svg, LIBXML_NONET | LIBXML_NOBLANKS | LIBXML_NOERROR | LIBXML_NOWARNING);

        libxml_use_internal_errors($previousErrors);

        if (! $loaded) {
            return '';
        }

        $allowedElements = [
            'svg', 'g', 'path', 'circle', 'ellipse', 'line', 'rect', 'polygon', 'polyline',
            'title', 'desc', 'defs', 'linearGradient', 'radialGradient', 'stop', 'symbol', 'use',
        ];

        $allowedAttributes = [
            'viewbox', 'd', 'fill', 'stroke', 'stroke-width', 'stroke-linecap', 'stroke-linejoin',
            'fill-rule', 'clip-rule', 'opacity', 'transform', 'cx', 'cy', 'r', 'rx', 'ry',
            'x', 'y', 'x1', 'y1', 'x2', 'y2', 'points', 'offset', 'stop-color', 'stop-opacity',
            'width', 'height', 'xmlns',
        ];

        $this->sanitizeSvgNode($dom->documentElement, $allowedElements, $allowedAttributes);

        $clean = $dom->saveXML($dom->documentElement);

        return is_string($clean) ? $clean : '';
    }

    /**
     * @param  array<int, string>  $allowedElements
     * @param  array<int, string>  $allowedAttributes
     */
    protected function sanitizeSvgNode(\DOMNode $node, array $allowedElements, array $allowedAttributes): void
    {
        $childrenToRemove = [];

        foreach ($node->childNodes as $child) {
            if ($child->nodeType !== XML_ELEMENT_NODE) {
                if ($child->nodeType === XML_COMMENT_NODE || $child->nodeType === XML_PI_NODE) {
                    $childrenToRemove[] = $child;
                }

                continue;
            }

            /** @var \DOMElement $child */
            if (! in_array($child->localName, $allowedElements, true)) {
                $childrenToRemove[] = $child;

                continue;
            }

            $this->sanitizeSvgNode($child, $allowedElements, $allowedAttributes);
        }

        foreach ($childrenToRemove as $child) {
            $node->removeChild($child);
        }

        if ($node->nodeType !== XML_ELEMENT_NODE) {
            return;
        }

        /** @var \DOMElement $node */
        $attributesToRemove = [];
        foreach ($node->attributes as $attribute) {
            $name = strtolower($attribute->name);

            if (str_starts_with($name, 'on') || ! in_array($name, $allowedAttributes, true)) {
                $attributesToRemove[] = $attribute->name;

                continue;
            }

            if (preg_match('/[<>]|javascript\s*:|vbscript\s*:|data\s*:/i', $attribute->value)) {
                $attributesToRemove[] = $attribute->name;
            }
        }

        foreach ($attributesToRemove as $name) {
            $node->removeAttribute($name);
        }
    }
}
