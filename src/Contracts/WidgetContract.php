<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Contracts;

use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;

interface WidgetContract
{
    public static function getType(): string;

    public static function getLabel(): string;

    public static function getIcon(): string;

    public static function getCategory(): string;

    public static function isContainer(): bool;

    /**
     * Default settings merged when a new block of this widget type is created
     * or rendered.
     *
     * @return array<string, mixed>
     */
    public static function getDefaultSettings(): array;

    /**
     * Filament v5 Schema components for the "Content" tab.
     *
     * @return array<int, Component>
     */
    public function getContentSchema(): array;

    /**
     * Filament v5 Schema components for the "Style" tab.
     *
     * @return array<int, Component>
     */
    public function getStyleSchema(): array;

    /**
     * Filament v5 Schema components for the "Advanced" tab.
     *
     * @return array<int, Component>
     */
    public function getAdvancedSchema(): array;

    /**
     * Builds the complete 3-tab (Content / Style / Advanced) Filament v5 Schema.
     */
    public function getSchema(Schema $schema): Schema;

    /**
     * Maps setting keys to CSS custom property names and units.
     * Example: ['padding' => ['var' => '--fpb-pad', 'unit' => 'px']]
     *
     * @return array<string, array{var: string, unit?: string}>
     */
    public function getCssMap(): array;

    /**
     * Render the widget into HTML.
     *
     * @param  array<string, mixed>  $element
     * @param  array<string, mixed>  $settings
     * @param  array<string, mixed>  $context
     */
    public function render(array $element, array $settings, string $childrenHtml = '', array $context = []): string;
}
