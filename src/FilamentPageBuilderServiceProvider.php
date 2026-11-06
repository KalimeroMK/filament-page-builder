<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder;

use Illuminate\Support\Facades\Blade;
use KalimeroMK\FilamentPageBuilder\Contracts\WidgetContract;
use KalimeroMK\FilamentPageBuilder\DynamicTags\CurrentDateDynamicTag;
use KalimeroMK\FilamentPageBuilder\DynamicTags\ModelAttributeDynamicTag;
use KalimeroMK\FilamentPageBuilder\DynamicTags\PageTitleDynamicTag;
use KalimeroMK\FilamentPageBuilder\Livewire\VisualPageBuilderEditor;
use KalimeroMK\FilamentPageBuilder\Registries\DynamicTagRegistry;
use KalimeroMK\FilamentPageBuilder\Registries\TemplateRegistry;
use KalimeroMK\FilamentPageBuilder\Registries\WidgetRegistry;
use KalimeroMK\FilamentPageBuilder\Rendering\CssGenerator;
use KalimeroMK\FilamentPageBuilder\Rendering\PageRenderer;
use KalimeroMK\FilamentPageBuilder\Services\PageBuilderManager;
use KalimeroMK\FilamentPageBuilder\Services\PageCacheService;
use KalimeroMK\FilamentPageBuilder\Services\PagePublishService;
use KalimeroMK\FilamentPageBuilder\Services\ThemeConditionMatcher;
use KalimeroMK\FilamentPageBuilder\Services\ThemeResolver;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentPageBuilderServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-page-builder';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasConfigFile()
            ->hasViews()
            ->hasRoute('web')
            ->hasMigrations([
                'create_fpb_pages_table',
                'create_fpb_page_revisions_table',
                'create_fpb_templates_table',
                'create_fpb_theme_templates_table',
                'create_fpb_form_submissions_table',
                'create_fpb_popups_table',
                'create_fpb_settings_table',
                'create_fpb_menus_table',
            ]);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(WidgetRegistry::class, fn () => new WidgetRegistry());
        $this->app->singleton(DynamicTagRegistry::class, fn () => new DynamicTagRegistry());
        $this->app->singleton(TemplateRegistry::class, fn () => new TemplateRegistry());
        $this->app->singleton(CssGenerator::class, fn () => new CssGenerator());
        $this->app->singleton(PageCacheService::class, fn () => new PageCacheService());
        $this->app->singleton(ThemeConditionMatcher::class, fn () => new ThemeConditionMatcher());
        $this->app->singleton(\KalimeroMK\FilamentPageBuilder\Registries\FormActionRegistry::class, fn () => new \KalimeroMK\FilamentPageBuilder\Registries\FormActionRegistry());
        $this->app->singleton(\KalimeroMK\FilamentPageBuilder\Services\PopupResolver::class, fn () => new \KalimeroMK\FilamentPageBuilder\Services\PopupResolver($this->app->make(ThemeConditionMatcher::class)));

        $this->app->singleton(PageRenderer::class, function ($app) {
            return new PageRenderer(
                widgets: $app->make(WidgetRegistry::class),
                dynamicTags: $app->make(DynamicTagRegistry::class),
                cssGenerator: $app->make(CssGenerator::class),
                cache: $app->make(PageCacheService::class),
            );
        });

        $this->app->singleton(ThemeResolver::class, function ($app) {
            return new ThemeResolver(
                matcher: $app->make(ThemeConditionMatcher::class),
                renderer: $app->make(PageRenderer::class),
            );
        });

        $this->app->singleton(PagePublishService::class, function ($app) {
            return new PagePublishService(
                cache: $app->make(PageCacheService::class),
            );
        });

        $this->app->singleton(PageBuilderManager::class, function ($app) {
            return new PageBuilderManager(
                widgets: $app->make(WidgetRegistry::class),
                dynamicTags: $app->make(DynamicTagRegistry::class),
                templates: $app->make(TemplateRegistry::class),
                renderer: $app->make(PageRenderer::class),
                publisher: $app->make(PagePublishService::class),
                theme: $app->make(ThemeResolver::class),
            );
        });
    }

    public function packageBooted(): void
    {
        $this->registerCoreWidgets();
        $this->registerCoreDynamicTags();
        $this->registerCoreFormActions();
        $this->registerLivewireComponents();
        $this->registerBladeDirectives();
        $this->registerAuthorizationGate();
    }

    protected function registerAuthorizationGate(): void
    {
        \Illuminate\Support\Facades\Gate::define('fpb.access_builder', function ($user = null, ...$arguments): bool {
            $area = $arguments[0] ?? 'builder';
            $callback = config('filament-page-builder.authorization.access_callback');

            if ($callback instanceof \Closure) {
                return (bool) $callback($user, $area);
            }

            return true;
        });
    }

    protected function registerCoreFormActions(): void
    {
        /** @var \KalimeroMK\FilamentPageBuilder\Registries\FormActionRegistry $registry */
        $registry = $this->app->make(\KalimeroMK\FilamentPageBuilder\Registries\FormActionRegistry::class);

        $registry->register(\KalimeroMK\FilamentPageBuilder\FormActions\SaveToDatabaseAction::class);
        $registry->register(\KalimeroMK\FilamentPageBuilder\FormActions\EmailAction::class);
        $registry->register(\KalimeroMK\FilamentPageBuilder\FormActions\RedirectAction::class);
        $registry->register(\KalimeroMK\FilamentPageBuilder\FormActions\WebhookAction::class);
        $registry->register(\KalimeroMK\FilamentPageBuilder\FormActions\AutoresponderAction::class);
    }

    protected function registerLivewireComponents(): void
    {
        Livewire::component('fpb-visual-editor', VisualPageBuilderEditor::class);
    }

    protected function registerCoreWidgets(): void
    {
        /** @var WidgetRegistry $registry */
        $registry = $this->app->make(WidgetRegistry::class);

        /** @var array<int, class-string<WidgetContract>> $widgets */
        $widgets = config('filament-page-builder.widgets', []);

        foreach ($widgets as $widgetClass) {
            if (is_string($widgetClass) && class_exists($widgetClass)) {
                $registry->register($widgetClass);
            }
        }
    }

    protected function registerCoreDynamicTags(): void
    {
        /** @var DynamicTagRegistry $registry */
        $registry = $this->app->make(DynamicTagRegistry::class);

        $registry->register(PageTitleDynamicTag::class);
        $registry->register(CurrentDateDynamicTag::class);
        $registry->register(ModelAttributeDynamicTag::class);
    }

    protected function registerBladeDirectives(): void
    {
        Blade::directive('renderPageBuilder', function (string $expression): string {
            return "<?php echo app(\\KalimeroMK\\FilamentPageBuilder\\Rendering\\PageRenderer::class)->renderPage({$expression})->toHtml(); ?>";
        });

        Blade::directive('filamentThemeRegion', function (string $expression): string {
            return "<?php echo app(\\KalimeroMK\\FilamentPageBuilder\\Services\\ThemeResolver::class)->renderRegion({$expression})->toHtml(); ?>";
        });
    }
}
