<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Widgets;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;

class FlipBoxWidget extends AbstractWidget
{
    public static function getType(): string
    {
        return 'flip_box';
    }

    public static function getLabel(): string
    {
        return 'Flip Box';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-arrow-path';
    }

    public static function getDefaultSettings(): array
    {
        return array_merge(static::baseDefaultSettings(), [
            'front_title' => 'Front Title',
            'front_text' => 'Hover to see the back side.',
            'back_title' => 'Back Title',
            'back_text' => 'Hidden content revealed on hover.',
            'height' => 220,
            'front_bg' => '#2563eb',
            'back_bg' => '#0f172a',
            'text_color' => '#ffffff',
            'border_radius' => 12,
        ]);
    }

    public function getCssMap(): array
    {
        return array_merge($this->baseCssMap(), [
            'height' => ['var' => '--fpb-height', 'unit' => 'px'],
            'front_bg' => ['var' => '--fpb-front-bg'],
            'back_bg' => ['var' => '--fpb-back-bg'],
            'text_color' => ['var' => '--fpb-color'],
            'border_radius' => ['var' => '--fpb-radius', 'unit' => 'px'],
        ]);
    }

    public function getContentSchema(): array
    {
        return [
            Grid::make(2)->schema([
                TextInput::make('front_title')->label('Front Title')->default('Front Title'),
                TextInput::make('back_title')->label('Back Title')->default('Back Title'),
            ]),
            Textarea::make('front_text')->label('Front Text')->rows(2),
            Textarea::make('back_text')->label('Back Text')->rows(2),
        ];
    }

    public function getStyleSchema(): array
    {
        return [
            Grid::make(3)->schema([
                ColorPicker::make('front_bg')->default('#2563eb'),
                ColorPicker::make('back_bg')->default('#0f172a'),
                ColorPicker::make('text_color')->default('#ffffff'),
            ]),
            Grid::make(2)->schema([
                TextInput::make('height')->label('Height (px)')->numeric()->default(220),
                TextInput::make('border_radius')->label('Border Radius (px)')->numeric()->default(12),
            ]),
        ];
    }

    protected function getViewName(): string
    {
        return 'filament-page-builder::widgets.flip-box';
    }
}
