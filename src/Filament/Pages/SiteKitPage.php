<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Filament\Pages;

use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use KalimeroMK\FilamentPageBuilder\FilamentPageBuilderPlugin;
use KalimeroMK\FilamentPageBuilder\Services\SiteKitService;
use KalimeroMK\FilamentPageBuilder\Support\PageBuilderAuthorizer;
use UnitEnum;

class SiteKitPage extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected string $view = 'filament-page-builder::filament.pages.site-kit';

    public string $importPayload = '';

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-archive-box-arrow-down';
    }

    public static function getNavigationLabel(): string
    {
        return 'Site Kit';
    }

    public static function getNavigationGroup(): string | UnitEnum | null
    {
        $panel = Filament::getCurrentOrDefaultPanel();

        if ($panel !== null && $panel->hasPlugin('filament-page-builder')) {
            return FilamentPageBuilderPlugin::get()->getNavigationGroup();
        }

        return 'Page Builder';
    }

    public static function getNavigationSort(): ?int
    {
        $panel = Filament::getCurrentOrDefaultPanel();

        if ($panel !== null && $panel->hasPlugin('filament-page-builder')) {
            $base = FilamentPageBuilderPlugin::get()->getNavigationSort();

            return $base !== null ? $base + 5 : 15;
        }

        return 15;
    }

    public static function canAccess(): bool
    {
        return PageBuilderAuthorizer::can('settings');
    }

    public function getTitle(): string
    {
        return 'Full Site Kit (Import / Export)';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Import Site Kit JSON')
                ->description('Paste a site kit exported from another Filament Page Builder site. Items are imported as drafts.')
                ->schema([
                    Textarea::make('importPayload')
                        ->label('Site Kit JSON')
                        ->rows(10),
                ]),
        ]);
    }

    public function exportKit(): void
    {
        abort_unless(PageBuilderAuthorizer::can('settings'), 403);

        $kit = app(SiteKitService::class)->exportKit();
        $json = json_encode($kit, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        $this->importPayload = $json;

        Notification::make()
            ->title('Site kit exported')
            ->body('The site kit JSON is now in the textarea below — copy and save it as a .json file.')
            ->success()
            ->send();
    }

    public function importKit(): void
    {
        abort_unless(PageBuilderAuthorizer::can('settings'), 403);

        $decoded = json_decode(trim($this->importPayload), true);

        if (! is_array($decoded)) {
            Notification::make()
                ->title('Invalid JSON')
                ->danger()
                ->send();

            return;
        }

        try {
            $counts = app(SiteKitService::class)->importKit($decoded, auth()->id());

            Notification::make()
                ->title('Site kit imported')
                ->body("Pages: {$counts['pages']}, Theme templates: {$counts['theme_templates']}, Templates: {$counts['templates']}, Popups: {$counts['popups']}, Menus: {$counts['menus']}")
                ->success()
                ->send();
        } catch (\InvalidArgumentException $e) {
            Notification::make()
                ->title('Invalid site kit')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
