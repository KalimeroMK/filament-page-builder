<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Widgets;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;

class TabsWidget extends AbstractWidget
{
    public static function getType(): string
    {
        return 'tabs';
    }

    public static function getLabel(): string
    {
        return 'Tabs';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-view-columns';
    }

    public static function getDefaultSettings(): array
    {
        return array_merge(static::baseDefaultSettings(), [
            'tabs' => [
                ['title' => 'Tab 1', 'content' => 'Content for Tab 1.'],
                ['title' => 'Tab 2', 'content' => 'Content for Tab 2.'],
            ],
            'active_color' => '#2563eb',
            'border_radius' => 8,
        ]);
    }

    public function getCssMap(): array
    {
        return array_merge($this->baseCssMap(), [
            'active_color' => ['var' => '--fpb-active-color'],
            'border_radius' => ['var' => '--fpb-radius', 'unit' => 'px'],
        ]);
    }

    public function getContentSchema(): array
    {
        return [
            Repeater::make('tabs')
                ->label('Tab Items')
                ->schema([
                    TextInput::make('title')
                        ->label('Tab Title')
                        ->required(),
                    Textarea::make('content')
                        ->label('Tab Content')
                        ->rows(3)
                        ->required(),
                ]),
        ];
    }

    public function getStyleSchema(): array
    {
        return [
            Grid::make(2)->schema([
                ColorPicker::make('active_color')
                    ->label('Active Tab Accent Color')
                    ->default('#2563eb'),
                TextInput::make('border_radius')
                    ->label('Border Radius (px)')
                    ->numeric()
                    ->default(8),
            ]),
        ];
    }

    protected function getViewName(): string
    {
        return 'filament-page-builder::widgets.tabs';
    }
}
