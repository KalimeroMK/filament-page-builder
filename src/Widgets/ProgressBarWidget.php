<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Widgets;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;

class ProgressBarWidget extends AbstractWidget
{
    public static function getType(): string
    {
        return 'progress_bar';
    }

    public static function getLabel(): string
    {
        return 'Progress Bar';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-chart-bar';
    }

    public static function getDefaultSettings(): array
    {
        return array_merge(static::baseDefaultSettings(), [
            'percent' => 65,
            'label' => 'Progress',
            'show_percent' => true,
            'bar_color' => '#2563eb',
            'track_color' => '#e2e8f0',
            'height' => 12,
            'border_radius' => 999,
        ]);
    }

    public function getCssMap(): array
    {
        return array_merge($this->baseCssMap(), [
            'bar_color' => ['var' => '--fpb-bar-color'],
            'track_color' => ['var' => '--fpb-track-color'],
            'height' => ['var' => '--fpb-height', 'unit' => 'px'],
            'border_radius' => ['var' => '--fpb-radius', 'unit' => 'px'],
        ]);
    }

    public function getContentSchema(): array
    {
        return [
            Grid::make(2)->schema([
                TextInput::make('percent')->label('Percent (0-100)')->numeric()->default(65)->required(),
                TextInput::make('label')->label('Label')->default('Progress'),
            ]),
            Toggle::make('show_percent')->label('Show Percent Text')->default(true),
        ];
    }

    public function getStyleSchema(): array
    {
        return [
            Grid::make(3)->schema([
                ColorPicker::make('bar_color')->default('#2563eb'),
                ColorPicker::make('track_color')->default('#e2e8f0'),
                TextInput::make('height')->label('Height (px)')->numeric()->default(12),
            ]),
        ];
    }

    protected function getViewName(): string
    {
        return 'filament-page-builder::widgets.progress-bar';
    }
}
