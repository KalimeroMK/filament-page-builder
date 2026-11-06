<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Facades;

use Illuminate\Support\Facades\Facade;
use KalimeroMK\FilamentPageBuilder\Enums\ThemeRegionType;
use KalimeroMK\FilamentPageBuilder\Models\Page;
use KalimeroMK\FilamentPageBuilder\Registries\DynamicTagRegistry;
use KalimeroMK\FilamentPageBuilder\Registries\TemplateRegistry;
use KalimeroMK\FilamentPageBuilder\Registries\WidgetRegistry;
use KalimeroMK\FilamentPageBuilder\Rendering\RenderedPage;
use KalimeroMK\FilamentPageBuilder\Services\PageBuilderManager;
use KalimeroMK\FilamentPageBuilder\Services\PagePublishService;
use KalimeroMK\FilamentPageBuilder\Services\ThemeResolver;

/**
 * @method static WidgetRegistry widgets()
 * @method static DynamicTagRegistry dynamicTags()
 * @method static TemplateRegistry templates()
 * @method static PagePublishService publisher()
 * @method static ThemeResolver theme()
 * @method static RenderedPage renderPage(Page $page, bool $useDraft = false, array $context = [])
 * @method static RenderedPage renderSchema(array $schema, array $context = [])
 * @method static RenderedPage renderThemeRegion(string|ThemeRegionType $region, array $context = [])
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
