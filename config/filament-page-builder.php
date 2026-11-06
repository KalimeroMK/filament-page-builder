<?php

declare(strict_types=1);

use KalimeroMK\FilamentPageBuilder\Models\Page;
use KalimeroMK\FilamentPageBuilder\Models\PageRevision;
use KalimeroMK\FilamentPageBuilder\Models\PageTemplate;
use KalimeroMK\FilamentPageBuilder\Widgets\AccordionWidget;
use KalimeroMK\FilamentPageBuilder\Widgets\AlertWidget;
use KalimeroMK\FilamentPageBuilder\Widgets\ButtonWidget;
use KalimeroMK\FilamentPageBuilder\Widgets\ContainerWidget;
use KalimeroMK\FilamentPageBuilder\Widgets\HeadingWidget;
use KalimeroMK\FilamentPageBuilder\Widgets\HtmlWidget;
use KalimeroMK\FilamentPageBuilder\Widgets\IconWidget;
use KalimeroMK\FilamentPageBuilder\Widgets\ImageGalleryWidget;
use KalimeroMK\FilamentPageBuilder\Widgets\ImageWidget;
use KalimeroMK\FilamentPageBuilder\Widgets\SpacerWidget;
use KalimeroMK\FilamentPageBuilder\Widgets\TabsWidget;
use KalimeroMK\FilamentPageBuilder\Widgets\TestimonialWidget;
use KalimeroMK\FilamentPageBuilder\Widgets\TextWidget;
use KalimeroMK\FilamentPageBuilder\Widgets\VideoWidget;

return [
    /*
    |--------------------------------------------------------------------------
    | Models
    |--------------------------------------------------------------------------
    */
    'models' => [
        'page' => Page::class,
        'page_revision' => PageRevision::class,
        'template' => PageTemplate::class,
        'theme_template' => \KalimeroMK\FilamentPageBuilder\Models\ThemeTemplate::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Table Names
    |--------------------------------------------------------------------------
    */
    'tables' => [
        'pages' => 'fpb_pages',
        'page_revisions' => 'fpb_page_revisions',
        'templates' => 'fpb_templates',
        'theme_templates' => 'fpb_theme_templates',
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    */
    'allow_raw_html' => false,

    /*
    |--------------------------------------------------------------------------
    | Routing Configuration
    |--------------------------------------------------------------------------
    | Controls whether the package registers public frontend routes and
    | protected preview routes for viewing draft_content before publishing.
    */
    'routing' => [
        'enabled' => true,
        'prefix' => 'pages',
        'preview_prefix' => '_builder/preview',
        'middleware' => ['web'],
        'preview_middleware' => ['web'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Page Layouts
    |--------------------------------------------------------------------------
    */
    'layouts' => [
        'default' => 'filament-page-builder::layouts.default',
        'canvas' => 'filament-page-builder::layouts.canvas',
        'full_width' => 'filament-page-builder::layouts.full-width',
    ],

    /*
    |--------------------------------------------------------------------------
    | Responsive Breakpoints (px)
    |--------------------------------------------------------------------------
    | Used by CssGenerator for @container and @media responsive rules.
    */
    'breakpoints' => [
        'tablet' => 1024,
        'mobile' => 767,
    ],

    /*
    |--------------------------------------------------------------------------
    | Render Cache Configuration
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'enabled' => true,
        'ttl' => 3600 * 24, // 24 hours
        'prefix' => 'fpb_page_render_',
    ],

    /*
    |--------------------------------------------------------------------------
    | Core Registered Widgets
    |--------------------------------------------------------------------------
    */
    'widgets' => [
        ContainerWidget::class,
        HeadingWidget::class,
        TextWidget::class,
        ButtonWidget::class,
        ImageWidget::class,
        SpacerWidget::class,
        IconWidget::class,
        VideoWidget::class,
        HtmlWidget::class,
        ImageGalleryWidget::class,
        AlertWidget::class,
        AccordionWidget::class,
        TabsWidget::class,
        TestimonialWidget::class,
    ],
];
