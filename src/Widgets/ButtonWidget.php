<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Widgets;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;

class ButtonWidget extends AbstractWidget
{
    public static function getType(): string
    {
        return 'button';
    }

    public static function getLabel(): string
    {
        return 'Button';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-cursor-arrow-rays';
    }

    public static function getDefaultSettings(): array
    {
        return array_merge(static::baseDefaultSettings(), [
            'label' => 'Click Here',
            'link' => '#',
            'style' => 'primary',
            'open_in_new_tab' => false,
            'background_color' => null,
            'text_color' => null,
            'border_radius' => 8,
            'font_size' => 16,
            'text_align' => 'left',
        ]);
    }

    public function getCssMap(): array
    {
        return array_merge($this->baseCssMap(), [
            'background_color' => ['var' => '--fpb-btn-bg'],
            'text_color' => ['var' => '--fpb-btn-color'],
            'border_radius' => ['var' => '--fpb-radius', 'unit' => 'px'],
            'font_size' => ['var' => '--fpb-font-size', 'unit' => 'px'],
            'text_align' => ['var' => '--fpb-text-align'],
        ]);
    }

    public function getContentSchema(): array
    {
        return [
            Grid::make(2)->schema([
                TextInput::make('label')
                    ->label('Button Label')
                    ->required()
                    ->default('Click Here'),
                TextInput::make('link')
                    ->label('URL / Link')
                    ->required()
                    ->default('#'),
            ]),
            Grid::make(2)->schema([
                Select::make('style')
                    ->label('Preset Style')
                    ->options([
                        'primary' => 'Primary',
                        'secondary' => 'Secondary',
                        'outline' => 'Outline',
                        'dark' => 'Dark',
                    ])
                    ->default('primary'),
                Toggle::make('open_in_new_tab')
                    ->label('Open in New Tab')
                    ->default(false),
            ]),
        ];
    }

    public function getStyleSchema(): array
    {
        return [
            Grid::make(2)->schema([
                ColorPicker::make('background_color')
                    ->label('Custom Background Color'),
                ColorPicker::make('text_color')
                    ->label('Custom Text Color'),
            ]),
            Grid::make(3)->schema([
                TextInput::make('border_radius')
                    ->label('Border Radius (px)')
                    ->numeric()
                    ->default(8),
                TextInput::make('font_size')
                    ->label('Font Size (px)')
                    ->numeric()
                    ->default(16),
                Select::make('text_align')
                    ->label('Alignment')
                    ->options([
                        'left' => 'Left',
                        'center' => 'Center',
                        'right' => 'Right',
                    ])
                    ->default('left'),
            ]),
        ];
    }

    protected function getViewName(): string
    {
        return 'filament-page-builder::widgets.button';
    }
}
