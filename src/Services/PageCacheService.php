<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Services;

use Illuminate\Support\Facades\Cache;
use KalimeroMK\FilamentPageBuilder\Models\Page;
use KalimeroMK\FilamentPageBuilder\Rendering\RenderedPage;

class PageCacheService
{
    public function isEnabled(): bool
    {
        return (bool) config('filament-page-builder.cache.enabled', true);
    }

    public function getCacheKey(Page $page): string
    {
        $prefix = (string) config('filament-page-builder.cache.prefix', 'fpb_page_render_');

        return $prefix . $page->getKey() . '_' . ($page->updated_at?->getTimestamp() ?? 0);
    }

    public function get(Page $page): ?RenderedPage
    {
        if (! $this->isEnabled()) {
            return null;
        }

        /** @var array{html: string, css: string}|null $cached */
        $cached = Cache::get($this->getCacheKey($page));

        if (! is_array($cached) || ! isset($cached['html'], $cached['css'])) {
            return null;
        }

        return new RenderedPage(
            html: (string) $cached['html'],
            css: (string) $cached['css'],
        );
    }

    public function put(Page $page, RenderedPage $rendered): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $ttl = (int) config('filament-page-builder.cache.ttl', 86400);

        Cache::put($this->getCacheKey($page), [
            'html' => $rendered->html,
            'css' => $rendered->css,
        ], $ttl);
    }

    public function forget(Page $page): void
    {
        Cache::forget($this->getCacheKey($page));
    }
}
