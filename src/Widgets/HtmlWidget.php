<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Widgets;

use Filament\Forms\Components\CodeEditor;
use Filament\Forms\Components\CodeEditor\Enums\Language;
use Filament\Forms\Components\Toggle;
use KalimeroMK\FilamentPageBuilder\Support\HtmlSanitizer;

class HtmlWidget extends AbstractWidget
{
    public static function getType(): string
    {
        return 'html';
    }

    public static function getLabel(): string
    {
        return 'HTML Embed';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-code-bracket';
    }

    public static function getDefaultSettings(): array
    {
        return array_merge(static::baseDefaultSettings(), [
            'html_content' => '<div class="custom-embed"><p>Custom HTML block</p></div>',
            'sanitize_html' => true,
        ]);
    }

    public function getCssMap(): array
    {
        return $this->baseCssMap();
    }

    public function getContentSchema(): array
    {
        return [
            CodeEditor::make('html_content')
                ->label('Custom HTML')
                ->language(Language::Html)
                ->default('<div class="custom-embed"><p>Custom HTML block</p></div>'),
            Toggle::make('sanitize_html')
                ->label('Sanitize HTML (Recommended for security)')
                ->visible(fn (): bool => (bool) config('filament-page-builder.allow_raw_html', false))
                ->default(true),
        ];
    }

    public function getStyleSchema(): array
    {
        return [];
    }

    public function render(array $element, array $settings, string $childrenHtml = '', array $context = []): string
    {
        $rawHtml = (string) ($settings['html_content'] ?? '');
        $allowRaw = (bool) config('filament-page-builder.allow_raw_html', false);
        $shouldSanitize = ! $allowRaw || ! isset($settings['sanitize_html']) || ! empty($settings['sanitize_html']);

        $settings['rendered_html'] = $shouldSanitize
            ? HtmlSanitizer::clean($rawHtml)
            : $rawHtml;

        return parent::render($element, $settings, $childrenHtml, $context);
    }

    protected function getViewName(): string
    {
        return 'filament-page-builder::widgets.html';
    }
}
