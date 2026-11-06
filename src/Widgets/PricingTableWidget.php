<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Widgets;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;

class PricingTableWidget extends AbstractWidget
{
    public static function getType(): string
    {
        return 'pricing_table';
    }

    public static function getLabel(): string
    {
        return 'Pricing Table';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-currency-dollar';
    }

    public static function getDefaultSettings(): array
    {
        return array_merge(static::baseDefaultSettings(), [
            'plan_name' => 'Pro',
            'price' => '€19',
            'period' => '/месец',
            'features' => [
                ['text' => 'Visual page builder'],
                ['text' => 'Theme Builder'],
                ['text' => 'Form Builder'],
            ],
            'button_label' => 'Избери план',
            'button_link' => '#',
            'highlighted' => false,
            'accent_color' => '#2563eb',
            'background_color' => '#ffffff',
            'text_color' => '#0f172a',
            'border_radius' => 12,
        ]);
    }

    public function getCssMap(): array
    {
        return array_merge($this->baseCssMap(), [
            'accent_color' => ['var' => '--fpb-accent'],
            'background_color' => ['var' => '--fpb-bg'],
            'text_color' => ['var' => '--fpb-color'],
            'border_radius' => ['var' => '--fpb-radius', 'unit' => 'px'],
        ]);
    }

    public function getContentSchema(): array
    {
        return [
            Grid::make(2)->schema([
                TextInput::make('plan_name')->label('Plan Name')->required()->default('Pro'),
                TextInput::make('price')->label('Price')->required()->default('€19'),
            ]),
            TextInput::make('period')->label('Period')->default('/месец'),
            Repeater::make('features')
                ->label('Features')
                ->schema([TextInput::make('text')->label('Feature')->required()]),
            Grid::make(2)->schema([
                TextInput::make('button_label')->label('Button Label')->default('Избери план'),
                TextInput::make('button_link')->label('Button Link')->default('#'),
            ]),
            Toggle::make('highlighted')->label('Highlight as Featured')->default(false),
        ];
    }

    public function getStyleSchema(): array
    {
        return [
            Grid::make(3)->schema([
                ColorPicker::make('accent_color')->default('#2563eb'),
                ColorPicker::make('background_color')->default('#ffffff'),
                ColorPicker::make('text_color')->default('#0f172a'),
            ]),
            TextInput::make('border_radius')->numeric()->default(12),
        ];
    }

    protected function getViewName(): string
    {
        return 'filament-page-builder::widgets.pricing-table';
    }
}
