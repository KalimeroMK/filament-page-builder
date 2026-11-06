<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Widgets;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;

class CountdownWidget extends AbstractWidget
{
    public static function getType(): string
    {
        return 'countdown';
    }

    public static function getLabel(): string
    {
        return 'Countdown';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-clock';
    }

    public static function getDefaultSettings(): array
    {
        return array_merge(static::baseDefaultSettings(), [
            'target_date' => now()->addDays(7)->format('Y-m-d\TH:i'),
            'background_color' => '#0f172a',
            'text_color' => '#ffffff',
            'border_radius' => 12,
        ]);
    }

    public function getCssMap(): array
    {
        return array_merge($this->baseCssMap(), [
            'background_color' => ['var' => '--fpb-bg'],
            'text_color' => ['var' => '--fpb-color'],
            'border_radius' => ['var' => '--fpb-radius', 'unit' => 'px'],
        ]);
    }

    public function getContentSchema(): array
    {
        return [
            TextInput::make('target_date')
                ->label('Target Date & Time (YYYY-MM-DD HH:MM)')
                ->placeholder('2026-12-31 23:59')
                ->required(),
        ];
    }

    public function getStyleSchema(): array
    {
        return [
            Grid::make(3)->schema([
                ColorPicker::make('background_color')->default('#0f172a'),
                ColorPicker::make('text_color')->default('#ffffff'),
                TextInput::make('border_radius')->numeric()->default(12),
            ]),
        ];
    }

    public function render(array $element, array $settings, string $childrenHtml = '', array $context = []): string
    {
        $raw = trim((string) ($settings['target_date'] ?? ''));
        $timestamp = strtotime($raw) ?: strtotime('+7 days');
        $settings['target_iso'] = date('c', $timestamp);

        return parent::render($element, $settings, $childrenHtml, $context);
    }

    protected function getViewName(): string
    {
        return 'filament-page-builder::widgets.countdown';
    }
}
