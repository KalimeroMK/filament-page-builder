<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder;

use Filament\Contracts\Plugin;
use Filament\Panel;
use KalimeroMK\FilamentPageBuilder\Filament\Pages\SiteSettingsPage;
use KalimeroMK\FilamentPageBuilder\Filament\Resources\PageResource;
use KalimeroMK\FilamentPageBuilder\Filament\Resources\ThemeTemplateResource;
use UnitEnum;

class FilamentPageBuilderPlugin implements Plugin
{
    protected bool $hasPageResource = true;

    protected bool $hasThemeTemplateResource = true;

    protected string | UnitEnum | null $navigationGroup = 'Page Builder';

    protected ?int $navigationSort = 10;

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }

    public function getId(): string
    {
        return 'filament-page-builder';
    }

    public function pageResource(bool $condition = true): static
    {
        $this->hasPageResource = $condition;

        return $this;
    }

    public function hasPageResource(): bool
    {
        return $this->hasPageResource;
    }

    public function themeTemplateResource(bool $condition = true): static
    {
        $this->hasThemeTemplateResource = $condition;

        return $this;
    }

    public function hasThemeTemplateResource(): bool
    {
        return $this->hasThemeTemplateResource;
    }

    public function navigationGroup(string | UnitEnum | null $group): static
    {
        $this->navigationGroup = $group;

        return $this;
    }

    public function getNavigationGroup(): string | UnitEnum | null
    {
        return $this->navigationGroup;
    }

    public function navigationSort(?int $sort): static
    {
        $this->navigationSort = $sort;

        return $this;
    }

    public function getNavigationSort(): ?int
    {
        return $this->navigationSort;
    }

    public function register(Panel $panel): void
    {
        $navigationGroup = $this->getNavigationGroup();

        if (is_string($navigationGroup) && $navigationGroup !== '') {
            $panel->navigationGroups([
                \Filament\Navigation\NavigationGroup::make($navigationGroup)
                    ->collapsible(),
            ]);
        }

        $resources = [];

        if ($this->hasPageResource()) {
            $resources[] = PageResource::class;
        }

        if ($this->hasThemeTemplateResource()) {
            $resources[] = ThemeTemplateResource::class;
        }

        if ($resources !== []) {
            $panel->resources($resources);
        }

        $panel->pages([
            SiteSettingsPage::class,
            \KalimeroMK\FilamentPageBuilder\Filament\Pages\SiteKitPage::class,
        ]);

        $panel->resources([
            \KalimeroMK\FilamentPageBuilder\Filament\Resources\FormSubmissionResource::class,
            \KalimeroMK\FilamentPageBuilder\Filament\Resources\MenuResource::class,
            \KalimeroMK\FilamentPageBuilder\Filament\Resources\CategoryResource::class,
            \KalimeroMK\FilamentPageBuilder\Filament\Resources\PostResource::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        // Hook for add-ons when the Filament panel boots
    }
}
