<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Widgets;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;

class StarRatingWidget extends AbstractWidget
{
    public static function getType(): string
    {
        return 'star_rating';
    }

    public static function getLabel(): string
    {
        return 'Star Rating';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-star';
    }

    public static function getDefaultSettings(): array
    {
        return array_merge(static::baseDefaultSettings(), [
            'rating' => 4.5,
            'count_text' => '',
            'star_size' => 24,
            'star_color' => '#f59e0b',
            'text_align' => 'left',
        ]);
    }

    public function getCssMap(): array
    {
        return array_merge($this->baseCssMap(), [
            'star_size' => ['var' => '--fpb-star-size', 'unit' => 'px'],
            'star_color' => ['var' => '--fpb-star-color'],
            'text_align' => ['var' => '--fpb-text-align'],
        ]);
    }

    public function getContentSchema(): array
    {
        return [
            Grid::make(2)->schema([
                Select::make('rating')
                    ->label('Rating')
                    ->options([
                        5 => '5.0', 4.5 => '4.5', 4 => '4.0', 3.5 => '3.5',
                        3 => '3.0', 2.5 => '2.5', 2 => '2.0', 1.5 => '1.5', 1 => '1.0', 0.5 => '0.5',
                    ])
                    ->default(4.5),
                TextInput::make('count_text')->label('Count Text (e.g. "128 reviews")'),
            ]),
        ];
    }

    public function getStyleSchema(): array
    {
        return [
            Grid::make(3)->schema([
                TextInput::make('star_size')->label('Star Size (px)')->numeric()->default(24),
                ColorPicker::make('star_color')->default('#f59e0b'),
                Select::make('text_align')->options(['left' => 'Left', 'center' => 'Center', 'right' => 'Right'])->default('left'),
            ]),
        ];
    }

    protected function getViewName(): string
    {
        return 'filament-page-builder::widgets.star-rating';
    }
}
