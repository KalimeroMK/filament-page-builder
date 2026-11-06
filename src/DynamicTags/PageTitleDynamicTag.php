<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\DynamicTags;

use KalimeroMK\FilamentPageBuilder\Contracts\DynamicTagContract;
use KalimeroMK\FilamentPageBuilder\Models\Page;

class PageTitleDynamicTag implements DynamicTagContract
{
    public static function getName(): string
    {
        return 'page.title';
    }

    public static function getLabel(): string
    {
        return 'Page Title';
    }

    public static function getGroup(): string
    {
        return 'Page';
    }

    public function resolve(array $parameters = [], array $context = []): mixed
    {
        $page = $context['page'] ?? null;

        if ($page instanceof Page) {
            return $page->title;
        }

        if (is_array($page) && isset($page['title'])) {
            return (string) $page['title'];
        }

        return $parameters['fallback'] ?? null;
    }
}
