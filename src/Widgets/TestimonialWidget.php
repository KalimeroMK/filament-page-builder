<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Widgets;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;

class TestimonialWidget extends AbstractWidget
{
    public static function getType(): string
    {
        return 'testimonial';
    }

    public static function getLabel(): string
    {
        return 'Testimonial';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-chat-bubble-bottom-center-text';
    }

    public static function getDefaultSettings(): array
    {
        return array_merge(static::baseDefaultSettings(), [
            'quote' => 'This page builder saved our team weeks of development time!',
            'author_name' => 'Ana Petrova',
            'author_role' => 'Product Manager',
            'avatar_url' => '',
            'rating' => 5,
            'background_color' => '#f8fafc',
            'text_color' => '#1e293b',
            'border_radius' => 12,
            'text_align' => 'left',
            'padding' => 24,
        ]);
    }

    public function getCssMap(): array
    {
        return array_merge($this->baseCssMap(), [
            'background_color' => ['var' => '--fpb-bg'],
            'text_color' => ['var' => '--fpb-color'],
            'border_radius' => ['var' => '--fpb-radius', 'unit' => 'px'],
            'text_align' => ['var' => '--fpb-text-align'],
        ]);
    }

    public function getContentSchema(): array
    {
        return [
            Textarea::make('quote')
                ->label('Quote Text')
                ->rows(3)
                ->required()
                ->default('This page builder saved our team weeks of development time!'),
            Grid::make(2)->schema([
                TextInput::make('author_name')
                    ->label('Author Name')
                    ->required()
                    ->default('Ana Petrova'),
                TextInput::make('author_role')
                    ->label('Author Role / Company')
                    ->default('Product Manager'),
            ]),
            Grid::make(2)->schema([
                TextInput::make('avatar_url')
                    ->label('Avatar Image URL'),
                Select::make('rating')
                    ->label('Star Rating')
                    ->options([
                        5 => '5 Stars',
                        4 => '4 Stars',
                        3 => '3 Stars',
                        2 => '2 Stars',
                        1 => '1 Star',
                    ])
                    ->default(5),
            ]),
        ];
    }

    public function getStyleSchema(): array
    {
        return [
            Grid::make(2)->schema([
                ColorPicker::make('background_color')
                    ->label('Card Background Color')
                    ->default('#f8fafc'),
                ColorPicker::make('text_color')
                    ->label('Text Color')
                    ->default('#1e293b'),
            ]),
            Grid::make(2)->schema([
                TextInput::make('border_radius')
                    ->label('Border Radius (px)')
                    ->numeric()
                    ->default(12),
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
        return 'filament-page-builder::widgets.testimonial';
    }
}
