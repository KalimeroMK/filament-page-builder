<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Widgets;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;

class ImageWidget extends AbstractWidget
{
    public static function getType(): string
    {
        return 'image';
    }

    public static function getLabel(): string
    {
        return 'Image';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-photo';
    }

    public static function getDefaultSettings(): array
    {
        return array_merge(static::baseDefaultSettings(), [
            'src' => 'https://placehold.co/800x450/e2e8f0/475569?text=Image',
            'alt' => '',
            'caption' => '',
            'link' => '',
            'open_in_new_tab' => false,
            'lazy_load' => true,
            'width' => null,
            'max_width' => '100%',
            'height' => null,
            'object_fit' => 'cover',
            'border_radius' => 0,
            'text_align' => 'center',
        ]);
    }

    public function getCssMap(): array
    {
        return array_merge($this->baseCssMap(), [
            'width' => ['var' => '--fpb-width', 'unit' => 'px'],
            'max_width' => ['var' => '--fpb-max-width', 'unit' => 'px'],
            'height' => ['var' => '--fpb-height', 'unit' => 'px'],
            'object_fit' => ['var' => '--fpb-object-fit'],
            'border_radius' => ['var' => '--fpb-radius', 'unit' => 'px'],
            'text_align' => ['var' => '--fpb-text-align'],
        ]);
    }

    public function getContentSchema(): array
    {
        return [
            TextInput::make('src')
                ->label('Image URL')
                ->required()
                ->default('https://placehold.co/800x450/e2e8f0/475569?text=Image'),
            Grid::make(2)->schema([
                TextInput::make('alt')
                    ->label('Alt Text'),
                TextInput::make('caption')
                    ->label('Caption'),
            ]),
            Grid::make(2)->schema([
                TextInput::make('link')
                    ->label('Optional Link URL'),
                Toggle::make('open_in_new_tab')
                    ->label('Open Link in New Tab')
                    ->default(false),
            ]),
            Toggle::make('lazy_load')
                ->label('Lazy Load Image')
                ->default(true),
        ];
    }

    public function getStyleSchema(): array
    {
        return [
            Grid::make(3)->schema([
                TextInput::make('width')
                    ->label('Width (px or %)'),
                TextInput::make('max_width')
                    ->label('Max Width (px or %)')
                    ->default('100%'),
                TextInput::make('height')
                    ->label('Height (px)'),
            ]),
            Grid::make(3)->schema([
                Select::make('object_fit')
                    ->label('Object Fit')
                    ->options([
                        'cover' => 'Cover',
                        'contain' => 'Contain',
                        'fill' => 'Fill',
                        'scale-down' => 'Scale Down',
                    ])
                    ->default('cover'),
                TextInput::make('border_radius')
                    ->label('Border Radius (px)')
                    ->numeric()
                    ->default(0),
                Select::make('text_align')
                    ->label('Alignment')
                    ->options([
                        'left' => 'Left',
                        'center' => 'Center',
                        'right' => 'Right',
                    ])
                    ->default('center'),
            ]),
        ];
    }

    protected function getViewName(): string
    {
        return 'filament-page-builder::widgets.image';
    }
}
