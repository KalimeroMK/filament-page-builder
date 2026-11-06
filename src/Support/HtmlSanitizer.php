<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Support;

class HtmlSanitizer
{
    /**
     * Sanitize rich text HTML by stripping executable tags, event handlers,
     * and dangerous URI schemes while preserving safe formatting tags.
     */
    public static function clean(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        // Remove script, style, iframe, object, embed, form tags and their contents
        $cleaned = (string) preg_replace(
            '#<(script|style|iframe|object|embed|applet|meta|link|form)\b[^>]*>.*?</\1>#is',
            '',
            $html,
        );

        // Remove self-closing or unclosed dangerous tags
        $cleaned = (string) preg_replace(
            '#</?(script|style|iframe|object|embed|applet|meta|link|form|input|button)\b[^>]*>#is',
            '',
            $cleaned,
        );

        // Remove inline event handlers such as onclick="", onerror="", onload=""
        $cleaned = (string) preg_replace(
            '/\s+on[a-zA-Z]+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i',
            '',
            $cleaned,
        );

        // Neutralize javascript:, vbscript:, and data:text/html URIs in href/src
        $cleaned = (string) preg_replace(
            '/\b(href|src|xlink:href)\s*=\s*(["\']?)\s*(?:javascript|vbscript|data\s*:\s*text\/html)\s*:[^"\'>\s]*\2/i',
            '$1="#"',
            $cleaned,
        );

        return $cleaned;
    }
}
