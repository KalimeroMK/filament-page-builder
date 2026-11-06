<?php

declare(strict_types=1);

use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use KalimeroMK\FilamentPageBuilder\Filament\Pages\MaintenanceSettingsPage;
use KalimeroMK\FilamentPageBuilder\Filament\Pages\SiteKitPage;
use KalimeroMK\FilamentPageBuilder\Filament\Pages\SiteSettingsPage;
use KalimeroMK\FilamentPageBuilder\Filament\Resources\CategoryResource;
use KalimeroMK\FilamentPageBuilder\Filament\Resources\FormSubmissionResource;
use KalimeroMK\FilamentPageBuilder\Filament\Resources\MenuResource;
use KalimeroMK\FilamentPageBuilder\Filament\Resources\PageResource;
use KalimeroMK\FilamentPageBuilder\Filament\Resources\PostResource;
use KalimeroMK\FilamentPageBuilder\Filament\Resources\ThemeTemplateResource;
use KalimeroMK\FilamentPageBuilder\FilamentPageBuilderPlugin;

it('groups every Page Builder admin item under one collapsible Page Builder menu', function (): void {
    $plugin = FilamentPageBuilderPlugin::make();
    expect($plugin->getNavigationGroup())->toBe('Page Builder');

    $panel = Panel::make()->id('admin');
    $plugin->register($panel);
    \Filament\Facades\Filament::setCurrentPanel($panel);

    $resources = [
        PageResource::class,
        ThemeTemplateResource::class,
        PostResource::class,
        CategoryResource::class,
        FormSubmissionResource::class,
        MenuResource::class,
        SiteSettingsPage::class,
        SiteKitPage::class,
        MaintenanceSettingsPage::class,
    ];

    foreach ($resources as $resource) {
        expect($resource::getNavigationGroup())->toBe('Page Builder');
    }

    $groups = $panel->getNavigationGroups();

    expect($groups)->toHaveCount(1)
        ->and($groups[0])->toBeInstanceOf(NavigationGroup::class)
        ->and($groups[0]->getLabel())->toBe('Page Builder')
        ->and($groups[0]->getIcon())->toBeNull()
        ->and($groups[0]->isCollapsible())->toBeTrue();
});
