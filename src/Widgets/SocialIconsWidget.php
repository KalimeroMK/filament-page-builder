<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Widgets;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;

class SocialIconsWidget extends AbstractWidget
{
    public static function getType(): string
    {
        return 'social_icons';
    }

    public static function getLabel(): string
    {
        return 'Social Icons';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-share';
    }

    public static function getDefaultSettings(): array
    {
        return array_merge(static::baseDefaultSettings(), [
            'items' => [
                ['network' => 'facebook', 'url' => 'https://facebook.com/'],
                ['network' => 'instagram', 'url' => 'https://instagram.com/'],
                ['network' => 'linkedin', 'url' => 'https://linkedin.com/'],
            ],
            'icon_size' => 20,
            'icon_color' => '#475569',
            'gap' => 14,
        ]);
    }

    public function getCssMap(): array
    {
        return array_merge($this->baseCssMap(), [
            'icon_size' => ['var' => '--fpb-icon-size', 'unit' => 'px'],
            'icon_color' => ['var' => '--fpb-icon-color'],
            'gap' => ['var' => '--fpb-gap', 'unit' => 'px'],
        ]);
    }

    public function getContentSchema(): array
    {
        return [
            Repeater::make('items')
                ->label('Social Links')
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('network')
                            ->options([
                                'facebook' => 'Facebook',
                                'x' => 'X (Twitter)',
                                'instagram' => 'Instagram',
                                'linkedin' => 'LinkedIn',
                                'youtube' => 'YouTube',
                                'github' => 'GitHub',
                            ])
                            ->default('facebook')
                            ->required(),
                        TextInput::make('url')->label('Profile URL')->required(),
                    ]),
                ]),
        ];
    }

    public function getStyleSchema(): array
    {
        return [
            Grid::make(3)->schema([
                TextInput::make('icon_size')->label('Icon Size (px)')->numeric()->default(20),
                ColorPicker::make('icon_color')->default('#475569'),
                TextInput::make('gap')->label('Gap (px)')->numeric()->default(14),
            ]),
        ];
    }

    protected function getViewName(): string
    {
        return 'filament-page-builder::widgets.social-icons';
    }
}
