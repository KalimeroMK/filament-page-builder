<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Widgets;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;

class SpacerWidget extends AbstractWidget
{
    public static function getType(): string
    {
        return 'spacer';
    }

    public static function getLabel(): string
    {
        return 'Spacer / Divider';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-arrows-up-down';
    }

    public static function getDefaultSettings(): array
    {
        return array_merge(static::baseDefaultSettings(), [
            'space' => 32,
            'space_tablet' => null,
            'space_mobile' => null,
            'show_divider' => false,
            'divider_style' => 'solid',
            'divider_weight' => 1,
            'divider_color' => '#e5e7eb',
            'divider_width' => '100%',
        ]);
    }

    public function getCssMap(): array
    {
        return array_merge($this->baseCssMap(), [
            'space' => ['var' => '--fpb-space', 'unit' => 'px'],
            'divider_style' => ['var' => '--fpb-divider-style'],
            'divider_weight' => ['var' => '--fpb-divider-weight', 'unit' => 'px'],
            'divider_color' => ['var' => '--fpb-divider-color'],
            'divider_width' => ['var' => '--fpb-divider-width', 'unit' => 'px'],
        ]);
    }

    public function getContentSchema(): array
    {
        return [
            Grid::make(3)->schema([
                TextInput::make('space')
                    ->label('Height (Desktop px)')
                    ->numeric()
                    ->default(32),
                TextInput::make('space_tablet')
                    ->label('Height (Tablet px)')
                    ->numeric(),
                TextInput::make('space_mobile')
                    ->label('Height (Mobile px)')
                    ->numeric(),
            ]),
            Toggle::make('show_divider')
                ->label('Show Divider Line')
                ->default(false),
        ];
    }

    public function getStyleSchema(): array
    {
        return [
            Grid::make(2)->schema([
                Select::make('divider_style')
                    ->label('Divider Style')
                    ->options([
                        'solid' => 'Solid',
                        'dashed' => 'Dashed',
                        'dotted' => 'Dotted',
                    ])
                    ->default('solid'),
                ColorPicker::make('divider_color')
                    ->label('Divider Color')
                    ->default('#e5e7eb'),
            ]),
            Grid::make(2)->schema([
                TextInput::make('divider_weight')
                    ->label('Line Weight (px)')
                    ->numeric()
                    ->default(1),
                TextInput::make('divider_width')
                    ->label('Line Width (e.g. 100% or 200px)')
                    ->default('100%'),
            ]),
        ];
    }

    protected function getViewName(): string
    {
        return 'filament-page-builder::widgets.spacer';
    }
}
