<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Widgets;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;

class GoogleMapsWidget extends AbstractWidget
{
    public static function getType(): string
    {
        return 'google_maps';
    }

    public static function getLabel(): string
    {
        return 'Google Maps';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-map-pin';
    }

    public static function getDefaultSettings(): array
    {
        return array_merge(static::baseDefaultSettings(), [
            'address' => 'Skopje, North Macedonia',
            'zoom' => 14,
            'height' => 400,
            'border_radius' => 12,
        ]);
    }

    public function getCssMap(): array
    {
        return array_merge($this->baseCssMap(), [
            'height' => ['var' => '--fpb-height', 'unit' => 'px'],
            'border_radius' => ['var' => '--fpb-radius', 'unit' => 'px'],
        ]);
    }

    public function getContentSchema(): array
    {
        return [
            Grid::make(2)->schema([
                TextInput::make('address')->label('Address / Place')->required()->default('Skopje, North Macedonia'),
                TextInput::make('zoom')->label('Zoom (1-20)')->numeric()->default(14),
            ]),
        ];
    }

    public function getStyleSchema(): array
    {
        return [
            Grid::make(2)->schema([
                TextInput::make('height')->label('Height (px)')->numeric()->default(400),
                TextInput::make('border_radius')->label('Border Radius (px)')->numeric()->default(12),
            ]),
        ];
    }

    public function render(array $element, array $settings, string $childrenHtml = '', array $context = []): string
    {
        $address = trim((string) ($settings['address'] ?? ''));
        $zoom = max(1, min(20, (int) ($settings['zoom'] ?? 14)));
        $settings['embed_url'] = 'https://www.google.com/maps?q=' . rawurlencode($address) . '&z=' . $zoom . '&output=embed';

        return parent::render($element, $settings, $childrenHtml, $context);
    }

    protected function getViewName(): string
    {
        return 'filament-page-builder::widgets.google-maps';
    }
}
