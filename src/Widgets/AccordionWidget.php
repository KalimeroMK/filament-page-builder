<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Widgets;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;

class AccordionWidget extends AbstractWidget
{
    public static function getType(): string
    {
        return 'accordion';
    }

    public static function getLabel(): string
    {
        return 'Accordion';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-queue-list';
    }

    public static function getDefaultSettings(): array
    {
        return array_merge(static::baseDefaultSettings(), [
            'items' => [
                ['title' => 'First Question', 'content' => 'Answer to the first question goes here.'],
                ['title' => 'Second Question', 'content' => 'Answer to the second question goes here.'],
            ],
            'open_first' => true,
            'border_color' => '#e2e8f0',
            'title_color' => '#0f172a',
            'border_radius' => 8,
        ]);
    }

    public function getCssMap(): array
    {
        return array_merge($this->baseCssMap(), [
            'border_color' => ['var' => '--fpb-border-color'],
            'title_color' => ['var' => '--fpb-title-color'],
            'border_radius' => ['var' => '--fpb-radius', 'unit' => 'px'],
        ]);
    }

    public function getContentSchema(): array
    {
        return [
            Repeater::make('items')
                ->label('Accordion Items')
                ->schema([
                    TextInput::make('title')
                        ->label('Title')
                        ->required(),
                    Textarea::make('content')
                        ->label('Content')
                        ->rows(3)
                        ->required(),
                ]),
            Toggle::make('open_first')
                ->label('Expand First Item by Default')
                ->default(true),
        ];
    }

    public function getStyleSchema(): array
    {
        return [
            Grid::make(3)->schema([
                ColorPicker::make('title_color')
                    ->label('Header Text Color')
                    ->default('#0f172a'),
                ColorPicker::make('border_color')
                    ->label('Border Color')
                    ->default('#e2e8f0'),
                TextInput::make('border_radius')
                    ->label('Border Radius (px)')
                    ->numeric()
                    ->default(8),
            ]),
        ];
    }

    protected function getViewName(): string
    {
        return 'filament-page-builder::widgets.accordion';
    }
}
