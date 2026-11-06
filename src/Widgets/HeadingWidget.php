<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Widgets;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;

class HeadingWidget extends AbstractWidget
{
    public static function getType(): string
    {
        return 'heading';
    }

    public static function getLabel(): string
    {
        return 'Heading';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-h1';
    }

    public static function getDefaultSettings(): array
    {
        return array_merge(static::baseDefaultSettings(), [
            'text' => 'Add Your Heading Text Here',
            'tag' => 'h2',
            'link' => '',
            'color' => '#111827',
            'font_size' => 32,
            'font_size_tablet' => null,
            'font_size_mobile' => null,
            'font_weight' => '700',
            'text_align' => 'left',
        ]);
    }

    public function getCssMap(): array
    {
        return array_merge($this->baseCssMap(), [
            'color' => ['var' => '--fpb-color'],
            'font_size' => ['var' => '--fpb-font-size', 'unit' => 'px'],
            'font_weight' => ['var' => '--fpb-font-weight'],
            'text_align' => ['var' => '--fpb-text-align'],
        ]);
    }

    public function getContentSchema(): array
    {
        return [
            TextInput::make('text')
                ->label('Title')
                ->required()
                ->default('Add Your Heading Text Here'),
            Grid::make(2)->schema([
                Select::make('tag')
                    ->label('HTML Tag')
                    ->options([
                        'h1' => 'H1',
                        'h2' => 'H2',
                        'h3' => 'H3',
                        'h4' => 'H4',
                        'h5' => 'H5',
                        'h6' => 'H6',
                    ])
                    ->default('h2'),
                TextInput::make('link')
                    ->label('Optional Link URL')
                    ->placeholder('https://...'),
            ]),
        ];
    }

    public function getStyleSchema(): array
    {
        return [
            ColorPicker::make('color')
                ->label('Text Color')
                ->default('#111827'),
            Grid::make(3)->schema([
                TextInput::make('font_size')
                    ->label('Size (Desktop px)')
                    ->numeric()
                    ->default(32),
                TextInput::make('font_size_tablet')
                    ->label('Size (Tablet px)')
                    ->numeric(),
                TextInput::make('font_size_mobile')
                    ->label('Size (Mobile px)')
                    ->numeric(),
            ]),
            Grid::make(2)->schema([
                Select::make('font_weight')
                    ->label('Font Weight')
                    ->options([
                        '400' => 'Normal (400)',
                        '500' => 'Medium (500)',
                        '600' => 'Semi Bold (600)',
                        '700' => 'Bold (700)',
                        '800' => 'Extra Bold (800)',
                    ])
                    ->default('700'),
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
        return 'filament-page-builder::widgets.heading';
    }
}
