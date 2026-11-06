<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Filament\Pages;

use Filament\Facades\Filament;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use KalimeroMK\FilamentPageBuilder\FilamentPageBuilderPlugin;
use KalimeroMK\FilamentPageBuilder\Services\SiteStyleService;
use UnitEnum;

class SiteSettingsPage extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected string $view = 'filament-page-builder::filament.pages.site-settings';

    /**
     * @var array<string, mixed>
     */
    public array $data = [];

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-adjustments-horizontal';
    }

    public static function getNavigationLabel(): string
    {
        return 'Site Settings';
    }

    public static function getNavigationGroup(): string | UnitEnum | null
    {
        $panel = Filament::getCurrentOrDefaultPanel();

        if ($panel !== null && $panel->hasPlugin('filament-page-builder')) {
            return FilamentPageBuilderPlugin::get()->getNavigationGroup();
        }

        return 'Content';
    }

    public static function getNavigationSort(): ?int
    {
        $panel = Filament::getCurrentOrDefaultPanel();

        if ($panel !== null && $panel->hasPlugin('filament-page-builder')) {
            $base = FilamentPageBuilderPlugin::get()->getNavigationSort();

            return $base !== null ? $base + 2 : 12;
        }

        return 12;
    }

    public function getTitle(): string
    {
        return 'Global Site Settings (Colors & Typography)';
    }

    public function mount(): void
    {
        $this->data = app(SiteStyleService::class)->values();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Global Colors')
                    ->description('Applied site-wide through CSS variables (--fpb-global-*).')
                    ->schema([
                        Grid::make(4)->schema([
                            ColorPicker::make('primary_color')
                                ->label('Primary Color')
                                ->required(),
                            ColorPicker::make('secondary_color')
                                ->label('Secondary Color')
                                ->required(),
                            ColorPicker::make('text_color')
                                ->label('Text Color')
                                ->required(),
                            ColorPicker::make('accent_color')
                                ->label('Accent Color')
                                ->required(),
                        ]),
                    ]),
                Section::make('Global Typography')
                    ->schema([
                        Grid::make(4)->schema([
                            TextInput::make('body_font_size')
                                ->label('Body Font Size (px)')
                                ->numeric()
                                ->required(),
                            TextInput::make('h1_font_size')
                                ->label('H1 Font Size (px)')
                                ->numeric()
                                ->required(),
                            TextInput::make('h2_font_size')
                                ->label('H2 Font Size (px)')
                                ->numeric()
                                ->required(),
                            TextInput::make('h3_font_size')
                                ->label('H3 Font Size (px)')
                                ->numeric()
                                ->required(),
                        ]),
                        Select::make('font_family')
                            ->label('Font Family')
                            ->options([
                                'system-ui, -apple-system, "Segoe UI", Roboto, sans-serif' => 'System UI (default)',
                                'Arial, Helvetica, sans-serif' => 'Arial / Helvetica',
                                'Georgia, "Times New Roman", serif' => 'Georgia (Serif)',
                                '"Courier New", Courier, monospace' => 'Courier New (Monospace)',
                            ])
                            ->required(),
                    ]),
            ]);
    }

    public function save(): void
    {
        $state = $this->form->getState();

        $allowed = array_keys(SiteStyleService::defaults());
        $values = array_intersect_key($state, array_flip($allowed));

        foreach (['body_font_size', 'h1_font_size', 'h2_font_size', 'h3_font_size'] as $sizeKey) {
            if (isset($values[$sizeKey])) {
                $values[$sizeKey] = max(8, min(200, (int) $values[$sizeKey]));
            }
        }

        app(SiteStyleService::class)->save($values);

        Notification::make()
            ->title('Global site settings saved')
            ->body('Colors and typography variables are now applied site-wide.')
            ->success()
            ->send();
    }
}
