<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Widgets;

use Filament\Forms\Components\CodeEditor;
use Filament\Forms\Components\CodeEditor\Enums\Language;
use Filament\Forms\Components\Select;
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
            'animation' => 'none',
            'sticky' => 'none',
            'sticky_offset' => 0,
            'hide_on_desktop' => false,
            'hide_on_tablet' => false,
            'hide_on_mobile' => false,
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
            'sticky_offset' => ['var' => '--fpb-sticky-offset', 'unit' => 'px'],
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
            Grid::make(3)->schema([
                Toggle::make('hide_on_desktop')->label('Hide on Desktop'),
                Toggle::make('hide_on_tablet')->label('Hide on Tablet'),
                Toggle::make('hide_on_mobile')->label('Hide on Mobile'),
            ]),
            Grid::make(3)->schema([
                Select::make('animation')
                    ->label('Entrance Animation')
                    ->options([
                        'none' => 'None',
                        'fade' => 'Fade In',
                        'slide-up' => 'Slide Up',
                        'zoom' => 'Zoom In',
                    ])
                    ->default('none'),
                Select::make('sticky')
                    ->label('Sticky Position')
                    ->options(['none' => 'None', 'top' => 'Sticky Top'])
                    ->default('none'),
                TextInput::make('sticky_offset')
                    ->label('Sticky Offset (px)')
                    ->numeric()
                    ->default(0),
            ]),
            CodeEditor::make('custom_css')
                ->label('Custom CSS (scoped to this element)')
                ->language(Language::Css)
                ->helperText('Declarations only, e.g. box-shadow: 0 4px 12px rgba(0,0,0,.15);'),
            TextInput::make('custom_attributes')
                ->label('Custom HTML Attributes')
                ->placeholder('data-track="hero" aria-label="Banner"'),
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

        $classes = ["fpb-element", $widgetClass, "fpb-el-{$id}"];

        $animation = (string) ($settings['animation'] ?? 'none');
        if (in_array($animation, ['fade', 'slide-up', 'zoom'], true)) {
            $classes[] = 'fpb-anim-' . $animation;
        }

        if (($settings['sticky'] ?? 'none') === 'top') {
            $classes[] = 'fpb-sticky-top';
        }

        foreach (['desktop', 'tablet', 'mobile'] as $device) {
            if (! empty($settings['hide_on_' . $device])) {
                $classes[] = 'fpb-hide-' . $device;
            }
        }

        if ($customSafe !== '') {
            $classes[] = $customSafe;
        }

        return trim(implode(' ', $classes));
    }

    abstract protected function getViewName(): string;

    /**
     * @param  array<string, mixed>  $element
     * @param  array<string, mixed>  $settings
     * @param  array<string, mixed>  $context
     */
    public function render(array $element, array $settings, string $childrenHtml = '', array $context = []): string
    {
        $html = View::make($this->getViewName(), [
            'element' => $element,
            'id' => (string) ($element['id'] ?? ''),
            'cssId' => preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($element['id'] ?? '')) ?: 'el',
            'htmlId' => preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($settings['element_id'] ?? '')),
            'classes' => $this->buildClassString($element, $settings, 'fpb-' . static::getType()),
            'settings' => $settings,
            'childrenHtml' => $childrenHtml,
            'context' => $context,
        ])->render();

        return $this->appendElementAttributes($html, $settings);
    }

    /**
     * Inject sanitized custom HTML attributes into the first opening tag of the widget wrapper.
     *
     * @param  array<string, mixed>  $settings
     */
    protected function appendElementAttributes(string $html, array $settings): string
    {
        $rawAttributes = trim((string) ($settings['custom_attributes'] ?? ''));

        if ($rawAttributes === '' || trim($html) === '') {
            return $html;
        }

        $safeAttributes = $this->sanitizeAttributes($rawAttributes);

        if ($safeAttributes === '') {
            return $html;
        }

        return (string) preg_replace_callback(
            '/<([a-zA-Z][a-zA-Z0-9]*)(\s|>)/',
            fn (array $m): string => '<' . $m[1] . ' ' . $safeAttributes . $m[2],
            $html,
            1,
        );
    }

    protected function sanitizeAttributes(string $raw): string
    {
        // Tokenize attributes, tolerating quoted values
        if (! preg_match_all(
            '/([a-zA-Z_:][a-zA-Z0-9_:.\-]*)(?:\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s"\'=<>`]+)))?/',
            $raw,
            $matches,
            PREG_SET_ORDER,
        )) {
            return '';
        }

        $safe = [];

        foreach ($matches as $match) {
            $name = strtolower($match[1]);
            $value = $match[2] !== '' ? $match[2] : ($match[3] !== '' ? $match[3] : ($match[4] ?? ''));

            // Reject event handlers and structural/dangerous attributes
            if (str_starts_with($name, 'on') || in_array($name, ['class', 'id', 'style', 'src', 'href', 'srcdoc', 'formaction', 'xlink:href'], true)) {
                continue;
            }

            // Allow only data-*, aria-*, role and title
            if (! str_starts_with($name, 'data-') && ! str_starts_with($name, 'aria-') && ! in_array($name, ['role', 'title'], true)) {
                continue;
            }

            if (preg_match('/[<>]|javascript\s*:|vbscript\s*:/i', (string) $value)) {
                continue;
            }

            $safe[] = $value === '' || $value === null
                ? $name
                : $name . '="' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '"';
        }

        return implode(' ', $safe);
    }
}
