<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Widgets;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;

class CtaWidget extends AbstractWidget
{
    public static function getType(): string
    {
        return 'cta';
    }

    public static function getLabel(): string
    {
        return 'Call to Action';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-megaphone';
    }

    public static function getDefaultSettings(): array
    {
        return array_merge(static::baseDefaultSettings(), [
            'title' => 'Ready to get started?',
            'subtitle' => 'Build your next page visually with Filament Page Builder.',
            'button_label' => 'Get Started',
            'button_link' => '#',
            'background_color' => '#2563eb',
            'text_color' => '#ffffff',
            'text_align' => 'center',
            'border_radius' => 12,
            'padding' => 48,
        ]);
    }

    public function getCssMap(): array
    {
        return array_merge($this->baseCssMap(), [
            'background_color' => ['var' => '--fpb-bg'],
            'text_color' => ['var' => '--fpb-color'],
            'text_align' => ['var' => '--fpb-text-align'],
            'border_radius' => ['var' => '--fpb-radius', 'unit' => 'px'],
        ]);
    }

    public function getContentSchema(): array
    {
        return [
            TextInput::make('title')->label('Title')->required()->default('Ready to get started?'),
            Textarea::make('subtitle')->label('Subtitle')->rows(2),
            Grid::make(2)->schema([
                TextInput::make('button_label')->label('Button Label')->default('Get Started'),
                TextInput::make('button_link')->label('Button Link')->default('#'),
            ]),
        ];
    }

    public function getStyleSchema(): array
    {
        return [
            Grid::make(2)->schema([
                ColorPicker::make('background_color')->default('#2563eb'),
                ColorPicker::make('text_color')->default('#ffffff'),
            ]),
            Grid::make(2)->schema([
                Select::make('text_align')->options(['left' => 'Left', 'center' => 'Center', 'right' => 'Right'])->default('center'),
                TextInput::make('border_radius')->numeric()->default(12),
            ]),
        ];
    }

    protected function getViewName(): string
    {
        return 'filament-page-builder::widgets.cta';
    }
}
