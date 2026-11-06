<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Widgets;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class SliderWidget extends AbstractWidget
{
    public static function getType(): string
    {
        return 'slider';
    }

    public static function getLabel(): string
    {
        return 'Slider / Carousel';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-photo';
    }

    public static function getDefaultSettings(): array
    {
        return array_merge(static::baseDefaultSettings(), [
            'slides' => [
                ['image' => 'https://placehold.co/1200x500/0f172a/f8fafc?text=Slide+1', 'title' => 'First Slide', 'subtitle' => 'Subtitle text'],
                ['image' => 'https://placehold.co/1200x500/1e293b/f8fafc?text=Slide+2', 'title' => 'Second Slide', 'subtitle' => 'More text'],
            ],
            'autoplay' => true,
            'interval' => 4000,
            'height' => 400,
            'border_radius' => 12,
        ]);
    }

    public function getCssMap(): array
    {
        return array_merge($this->baseCssMap(), [
            'height' => ['var' => '--fpb-slider-height', 'unit' => 'px'],
            'border_radius' => ['var' => '--fpb-radius', 'unit' => 'px'],
        ]);
    }

    public function getContentSchema(): array
    {
        return [
            Repeater::make('slides')
                ->label('Slides')
                ->schema([
                    TextInput::make('image')->label('Image URL')->required(),
                    TextInput::make('title')->label('Title'),
                    TextInput::make('subtitle')->label('Subtitle'),
                    TextInput::make('link')->label('Optional Link'),
                ]),
            Toggle::make('autoplay')->label('Autoplay')->default(true),
            TextInput::make('interval')->label('Interval (ms)')->numeric()->default(4000),
            TextInput::make('height')->label('Height (px)')->numeric()->default(400),
        ];
    }

    public function getStyleSchema(): array
    {
        return [
            TextInput::make('border_radius')->label('Border Radius (px)')->numeric()->default(12),
        ];
    }

    protected function getViewName(): string
    {
        return 'filament-page-builder::widgets.slider';
    }
}
