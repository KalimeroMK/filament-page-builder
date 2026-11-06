<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Facades;

use Illuminate\Support\Facades\Facade;
use KalimeroMK\FilamentPageBuilder\Models\Page;
use KalimeroMK\FilamentPageBuilder\Registries\DynamicTagRegistry;
use KalimeroMK\FilamentPageBuilder\Registries\WidgetRegistry;
use KalimeroMK\FilamentPageBuilder\Rendering\RenderedPage;
use KalimeroMK\FilamentPageBuilder\Services\PageBuilderManager;

/**
 * @method static WidgetRegistry widgets()
 * @method static DynamicTagRegistry dynamicTags()
 * @method static RenderedPage renderPage(Page $page, bool $useDraft = false, array $context = [])
 * @method static RenderedPage renderSchema(array $schema, array $context = [])
 *
 * @see PageBuilderManager
 */
class PageBuilder extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PageBuilderManager::class;
    }
}
