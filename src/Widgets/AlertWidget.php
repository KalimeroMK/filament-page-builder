<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Widgets;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class AlertWidget extends AbstractWidget
{
    public static function getType(): string
    {
        return 'alert';
    }

    public static function getLabel(): string
    {
        return 'Alert / Notice';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-exclamation-triangle';
    }

    public static function getDefaultSettings(): array
    {
        return array_merge(static::baseDefaultSettings(), [
            'alert_type' => 'info',
            'title' => 'Important Notice',
            'description' => 'Here is some helpful information for your visitors.',
            'dismissible' => true,
            'border_radius' => 8,
        ]);
    }

    public function getCssMap(): array
    {
        return array_merge($this->baseCssMap(), [
            'border_radius' => ['var' => '--fpb-radius', 'unit' => 'px'],
        ]);
    }

    public function getContentSchema(): array
    {
        return [
            Select::make('alert_type')
                ->label('Alert Type')
                ->options([
                    'info' => 'Info (Blue)',
                    'success' => 'Success (Green)',
                    'warning' => 'Warning (Amber)',
                    'danger' => 'Danger (Red)',
                ])
                ->default('info')
                ->required(),
            TextInput::make('title')
                ->label('Title')
                ->required()
                ->default('Important Notice'),
            Textarea::make('description')
                ->label('Description')
                ->rows(3),
            Toggle::make('dismissible')
                ->label('Show Dismiss (Close) Button')
                ->default(true),
        ];
    }

    public function getStyleSchema(): array
    {
        return [
            TextInput::make('border_radius')
                ->label('Border Radius (px)')
                ->numeric()
                ->default(8),
        ];
    }

    protected function getViewName(): string
    {
        return 'filament-page-builder::widgets.alert';
    }
}
