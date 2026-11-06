<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Widgets;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\View;
use KalimeroMK\FilamentPageBuilder\Contracts\WidgetContract;

abstract class AbstractWidget implements WidgetContract
{
    public static function isContainer(): bool
    {
        return false;
    }

    public static function getCategory(): string
    {
        return 'basic';
    }

    /**
     * Base default settings shared across all widgets (Advanced tab).
     *
     * @return array<string, mixed>
     */
    protected static function baseDefaultSettings(): array
    {
        return [
            'padding' => 0,
            'padding_tablet' => null,
            'padding_mobile' => null,
            'margin_top' => 0,
            'margin_bottom' => 0,
            'css_classes' => '',
            'element_id' => '',
            'hide_element' => false,
        ];
    }

    /**
     * Common CSS variables map for spacing. Widgets merge their own variables with this.
     *
     * @return array<string, array{var: string, unit?: string}>
     */
    protected function baseCssMap(): array
    {
        return [
            'padding' => ['var' => '--fpb-padding', 'unit' => 'px'],
            'margin_top' => ['var' => '--fpb-margin-top', 'unit' => 'px'],
            'margin_bottom' => ['var' => '--fpb-margin-bottom', 'unit' => 'px'],
        ];
    }

    public function getAdvancedSchema(): array
    {
        return [
            Grid::make(3)->schema([
                TextInput::make('padding')
                    ->label('Padding (Desktop px)')
                    ->numeric()
                    ->default(0),
                TextInput::make('padding_tablet')
                    ->label('Padding (Tablet px)')
                    ->numeric(),
                TextInput::make('padding_mobile')
                    ->label('Padding (Mobile px)')
                    ->numeric(),
            ]),
            Grid::make(2)->schema([
                TextInput::make('margin_top')
                    ->label('Margin Top (px)')
                    ->numeric()
                    ->default(0),
                TextInput::make('margin_bottom')
                    ->label('Margin Bottom (px)')
                    ->numeric()
                    ->default(0),
            ]),
            Grid::make(2)->schema([
                TextInput::make('element_id')
                    ->label('CSS ID')
                    ->placeholder('my-section'),
                TextInput::make('css_classes')
                    ->label('CSS Classes')
                    ->placeholder('custom-class another-class'),
            ]),
            Toggle::make('hide_element')
                ->label('Hide Element')
                ->default(false),
        ];
    }

    /**
     * Builds the Elementor-style 3-tab inspector schema (Content / Style / Advanced)
     * using Filament v5 `Filament\Schemas\Schema`.
     */
    public function getSchema(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('widget_inspector_tabs')
                ->tabs([
                    Tab::make('Content')
                        ->icon('heroicon-o-pencil-square')
                        ->schema($this->getContentSchema()),
                    Tab::make('Style')
                        ->icon('heroicon-o-paint-brush')
                        ->schema($this->getStyleSchema()),
                    Tab::make('Advanced')
                        ->icon('heroicon-o-cog-6-tooth')
                        ->schema($this->getAdvancedSchema()),
                ]),
        ]);
    }

    /**
     * Build the CSS class list for an element wrapper.
     *
     * @param  array<string, mixed>  $element
     * @param  array<string, mixed>  $settings
     */
    protected function buildClassString(array $element, array $settings, string $widgetClass): string
    {
        $id = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($element['id'] ?? 'el')) ?: 'el';
        $custom = trim((string) ($settings['css_classes'] ?? ''));
        $customSafe = preg_replace('/[^a-zA-Z0-9_\-\s]/', '', $custom) ?: '';

        return trim("fpb-element {$widgetClass} fpb-el-{$id} {$customSafe}");
    }

    abstract protected function getViewName(): string;

    /**
     * @param  array<string, mixed>  $element
     * @param  array<string, mixed>  $settings
     * @param  array<string, mixed>  $context
     */
    public function render(array $element, array $settings, string $childrenHtml = '', array $context = []): string
    {
        return View::make($this->getViewName(), [
            'element' => $element,
            'id' => (string) ($element['id'] ?? ''),
            'htmlId' => preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($settings['element_id'] ?? '')),
            'classes' => $this->buildClassString($element, $settings, 'fpb-' . static::getType()),
            'settings' => $settings,
            'childrenHtml' => $childrenHtml,
            'context' => $context,
        ])->render();
    }
}
