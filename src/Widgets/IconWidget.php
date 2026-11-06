<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Widgets;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;

class IconWidget extends AbstractWidget
{
    public static function getType(): string
    {
        return 'icon';
    }

    public static function getLabel(): string
    {
        return 'Icon';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-sparkles';
    }

    public static function getDefaultSettings(): array
    {
        return array_merge(static::baseDefaultSettings(), [
            'icon' => 'heroicon-o-star',
            'link' => '',
            'open_in_new_tab' => false,
            'icon_size' => 40,
            'icon_size_tablet' => null,
            'icon_size_mobile' => null,
            'icon_color' => '#2563eb',
            'background_color' => null,
            'border_radius' => '50%',
            'text_align' => 'center',
        ]);
    }

    public function getCssMap(): array
    {
        return array_merge($this->baseCssMap(), [
            'icon_size' => ['var' => '--fpb-icon-size', 'unit' => 'px'],
            'icon_color' => ['var' => '--fpb-icon-color'],
            'background_color' => ['var' => '--fpb-icon-bg'],
            'border_radius' => ['var' => '--fpb-radius', 'unit' => 'px'],
            'text_align' => ['var' => '--fpb-text-align'],
        ]);
    }

    public function getContentSchema(): array
    {
        return [
            Select::make('icon')
                ->label('Icon')
                ->searchable()
                ->options([
                    'heroicon-o-star' => 'Star',
                    'heroicon-o-sparkles' => 'Sparkles',
                    'heroicon-o-bolt' => 'Bolt',
                    'heroicon-o-heart' => 'Heart',
                    'heroicon-o-check-badge' => 'Check Badge',
                    'heroicon-o-shield-check' => 'Shield Check',
                    'heroicon-o-rocket-launch' => 'Rocket Launch',
                    'heroicon-o-globe-alt' => 'Globe',
                    'heroicon-o-light-bulb' => 'Light Bulb',
                    'heroicon-o-fire' => 'Fire',
                ])
                ->default('heroicon-o-star')
                ->required(),
            Grid::make(2)->schema([
                TextInput::make('link')
                    ->label('Optional Link URL'),
                Toggle::make('open_in_new_tab')
                    ->label('Open Link in New Tab')
                    ->default(false),
            ]),
        ];
    }

    public function getStyleSchema(): array
    {
        return [
            Grid::make(3)->schema([
                TextInput::make('icon_size')
                    ->label('Size (Desktop px)')
                    ->numeric()
                    ->default(40),
                TextInput::make('icon_size_tablet')
                    ->label('Size (Tablet px)')
                    ->numeric(),
                TextInput::make('icon_size_mobile')
                    ->label('Size (Mobile px)')
                    ->numeric(),
            ]),
            Grid::make(2)->schema([
                ColorPicker::make('icon_color')
                    ->label('Icon Color')
                    ->default('#2563eb'),
                ColorPicker::make('background_color')
                    ->label('Background Color'),
            ]),
            Grid::make(2)->schema([
                TextInput::make('border_radius')
                    ->label('Border Radius (px or %)')
                    ->default('50%'),
                Select::make('text_align')
                    ->label('Alignment')
                    ->options([
                        'left' => 'Left',
                        'center' => 'Center',
                        'right' => 'Right',
                    ])
                    ->default('center'),
            ]),
        ];
    }

    protected function getViewName(): string
    {
        return 'filament-page-builder::widgets.icon';
    }
}
