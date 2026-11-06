<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use KalimeroMK\FilamentPageBuilder\FilamentPageBuilderServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        $providers = [
            LivewireServiceProvider::class,
            BladeIconsServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            SupportServiceProvider::class,
            ActionsServiceProvider::class,
            SchemasServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            NotificationsServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
            FilamentServiceProvider::class,
            FilamentPageBuilderServiceProvider::class,
        ];

        return array_values(array_filter($providers, fn (string $provider): bool => class_exists($provider)));
    }

    protected function defineDatabaseMigrations(): void
    {
        $createPages = include __DIR__ . '/../database/migrations/create_fpb_pages_table.php.stub';
        $createPages->up();

        $createRevisions = include __DIR__ . '/../database/migrations/create_fpb_page_revisions_table.php.stub';
        $createRevisions->up();

        $createTemplates = include __DIR__ . '/../database/migrations/create_fpb_templates_table.php.stub';
        $createTemplates->up();
    }
}
