<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Widgets;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;

class ImageGalleryWidget extends AbstractWidget
{
    public static function getType(): string
    {
        return 'image_gallery';
    }

    public static function getLabel(): string
    {
        return 'Image Gallery';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-squares-2x2';
    }

    public static function getDefaultSettings(): array
    {
        return array_merge(static::baseDefaultSettings(), [
            'images' => [
                ['src' => 'https://placehold.co/600x400/e2e8f0/475569?text=Slide+1', 'alt' => 'Image 1', 'caption' => ''],
                ['src' => 'https://placehold.co/600x400/e2e8f0/475569?text=Slide+2', 'alt' => 'Image 2', 'caption' => ''],
                ['src' => 'https://placehold.co/600x400/e2e8f0/475569?text=Slide+3', 'alt' => 'Image 3', 'caption' => ''],
            ],
            'columns' => 3,
            'columns_tablet' => 2,
            'columns_mobile' => 1,
            'gap' => 16,
            'border_radius' => 8,
            'lightbox' => true,
        ]);
    }

    public function getCssMap(): array
    {
        return array_merge($this->baseCssMap(), [
            'columns' => ['var' => '--fpb-gallery-cols'],
            'gap' => ['var' => '--fpb-gap', 'unit' => 'px'],
            'border_radius' => ['var' => '--fpb-radius', 'unit' => 'px'],
        ]);
    }

    public function getContentSchema(): array
    {
        return [
            Repeater::make('images')
                ->label('Gallery Images')
                ->schema([
                    TextInput::make('src')
                        ->label('Image URL')
                        ->required(),
                    Grid::make(2)->schema([
                        TextInput::make('alt')
                            ->label('Alt Text'),
                        TextInput::make('caption')
                            ->label('Caption'),
                    ]),
                ]),
            Toggle::make('lightbox')
                ->label('Enable Lightbox Preview on Click')
                ->default(true),
        ];
    }

    public function getStyleSchema(): array
    {
        $colOptions = [
            1 => '1 Column',
            2 => '2 Columns',
            3 => '3 Columns',
            4 => '4 Columns',
            6 => '6 Columns',
        ];

        return [
            Grid::make(3)->schema([
                Select::make('columns')
                    ->label('Columns (Desktop)')
                    ->options($colOptions)
                    ->default(3),
                Select::make('columns_tablet')
                    ->label('Columns (Tablet)')
                    ->options($colOptions)
                    ->default(2),
                Select::make('columns_mobile')
                    ->label('Columns (Mobile)')
                    ->options($colOptions)
                    ->default(1),
            ]),
            Grid::make(2)->schema([
                TextInput::make('gap')
                    ->label('Grid Gap (px)')
                    ->numeric()
                    ->default(16),
                TextInput::make('border_radius')
                    ->label('Image Border Radius (px)')
                    ->numeric()
                    ->default(8),
            ]),
        ];
    }

    protected function getViewName(): string
    {
        return 'filament-page-builder::widgets.image-gallery';
    }
}
