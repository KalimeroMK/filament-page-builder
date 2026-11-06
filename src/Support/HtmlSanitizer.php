<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Support;

use Illuminate\Support\Str;

class HtmlSanitizer
{
    /**
     * Sanitize rich text HTML using Symfony's DOM-based HTML sanitizer (bundled with Filament v5)
     * plus defense-in-depth stripping of executable tags.
     */
    public static function clean(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        // Pre-strip script/style blocks completely before DOM sanitization
        $preCleaned = (string) preg_replace(
            '#<(script|style|iframe|object|embed|applet|frameset|frame)\b[^>]*>.*?</\1>#is',
            '',
            $html,
        );

        return Str::sanitizeHtml($preCleaned);
    }

    /**
     * Sanitize a URL for safe use in href/src attributes.
     * Allows safe relative paths (starting with /, #, ?) and http/https/mailto/tel URLs.
     */
    public static function sanitizeUrl(string $url, string $default = '#'): string
    {
        $trimmed = trim($url);

        if ($trimmed === '') {
            return $default;
        }

        // Reject any ASCII control characters or whitespace inside scheme/path
        if (preg_match('/[\x00-\x1F\x7F]/', $trimmed)) {
            return $default;
        }

        if (str_starts_with($trimmed, '//')) {
            return $default;
        }

        // Allow safe relative URLs (/path, #anchor, ?query)
        if (str_starts_with($trimmed, '/') || str_starts_with($trimmed, '#') || str_starts_with($trimmed, '?')) {
            return $trimmed;
        }

        $sanitized = Str::sanitizeUrl($trimmed, ['http', 'https', 'mailto', 'tel']);

        return ($sanitized !== null && $sanitized !== '') ? $sanitized : $default;
    }
}
