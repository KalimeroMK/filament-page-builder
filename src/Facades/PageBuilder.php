<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Facades;

use Illuminate\Support\Facades\Facade;
use KalimeroMK\FilamentPageBuilder\Models\Page;
use KalimeroMK\FilamentPageBuilder\Registries\DynamicTagRegistry;
use KalimeroMK\FilamentPageBuilder\Registries\TemplateRegistry;
use KalimeroMK\FilamentPageBuilder\Registries\WidgetRegistry;
use KalimeroMK\FilamentPageBuilder\Rendering\RenderedPage;
use KalimeroMK\FilamentPageBuilder\Services\PageBuilderManager;
use KalimeroMK\FilamentPageBuilder\Services\PagePublishService;

/**
 * @method static WidgetRegistry widgets()
 * @method static DynamicTagRegistry dynamicTags()
 * @method static TemplateRegistry templates()
 * @method static PagePublishService publisher()
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
