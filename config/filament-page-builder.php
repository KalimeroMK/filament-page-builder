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
use KalimeroMK\FilamentPageBuilder\Widgets\LoopWidget;
use KalimeroMK\FilamentPageBuilder\Widgets\FormWidget;
use KalimeroMK\FilamentPageBuilder\Widgets\GlobalWidget;
use KalimeroMK\FilamentPageBuilder\Widgets\SliderWidget;
use KalimeroMK\FilamentPageBuilder\Widgets\PricingTableWidget;
use KalimeroMK\FilamentPageBuilder\Widgets\CountdownWidget;
use KalimeroMK\FilamentPageBuilder\Widgets\ProgressBarWidget;
use KalimeroMK\FilamentPageBuilder\Widgets\SocialIconsWidget;
use KalimeroMK\FilamentPageBuilder\Widgets\StarRatingWidget;
use KalimeroMK\FilamentPageBuilder\Widgets\GoogleMapsWidget;
use KalimeroMK\FilamentPageBuilder\Widgets\FlipBoxWidget;
use KalimeroMK\FilamentPageBuilder\Widgets\CtaWidget;
use KalimeroMK\FilamentPageBuilder\Widgets\NavMenuWidget;

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
        'form_submission' => \KalimeroMK\FilamentPageBuilder\Models\FormSubmission::class,
        'popup' => \KalimeroMK\FilamentPageBuilder\Models\Popup::class,
        'menu' => \KalimeroMK\FilamentPageBuilder\Models\Menu::class,
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
        'form_submissions' => 'fpb_form_submissions',
        'popups' => 'fpb_popups',
        'settings' => 'fpb_settings',
        'menus' => 'fpb_menus',
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
    | Loop Builder Allowed Models
    |--------------------------------------------------------------------------
    | Restrict which Eloquent model classes can be used in the Loop Builder.
    | Leave empty to allow any model.
    */
    'loop' => [
        'allowed_models' => [],
        'allowed_fields' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Form Builder Settings
    |--------------------------------------------------------------------------
    */
    'forms' => [
        'rate_limit' => '10,1',
        'webhook_allowed_hosts' => [],
        'allow_external_redirects' => false,
        'turnstile' => [
            'site_key' => env('FPB_TURNSTILE_SITE_KEY', ''),
            'secret_key' => env('FPB_TURNSTILE_SECRET_KEY', ''),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Authorization (Role Manager)
    |--------------------------------------------------------------------------
    | Optional closure: fn ($user, string $area): bool
    | Areas: pages, theme_templates, menus, submissions, settings, builder
    */
    'authorization' => [
        'access_callback' => null,
        'default_allow' => true,
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
        LoopWidget::class,
        FormWidget::class,
        GlobalWidget::class,
        SliderWidget::class,
        PricingTableWidget::class,
        CountdownWidget::class,
        ProgressBarWidget::class,
        SocialIconsWidget::class,
        StarRatingWidget::class,
        GoogleMapsWidget::class,
        FlipBoxWidget::class,
        CtaWidget::class,
        NavMenuWidget::class,
    ],
];
