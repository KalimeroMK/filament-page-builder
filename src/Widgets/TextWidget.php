<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Widgets;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use KalimeroMK\FilamentPageBuilder\Support\HtmlSanitizer;

class TextWidget extends AbstractWidget
{
    public static function getType(): string
    {
        return 'text';
    }

    public static function getLabel(): string
    {
        return 'Rich Text';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-bars-3-bottom-left';
    }

    public static function getDefaultSettings(): array
    {
        return array_merge(static::baseDefaultSettings(), [
            'content' => '<p>Write your paragraph text here.</p>',
            'color' => '#374151',
            'font_size' => 16,
            'font_size_tablet' => null,
            'font_size_mobile' => null,
            'text_align' => 'left',
        ]);
    }

    public function getCssMap(): array
    {
        return array_merge($this->baseCssMap(), [
            'color' => ['var' => '--fpb-color'],
            'font_size' => ['var' => '--fpb-font-size', 'unit' => 'px'],
            'text_align' => ['var' => '--fpb-text-align'],
        ]);
    }

    public function getContentSchema(): array
    {
        return [
            RichEditor::make('content')
                ->label('Content')
                ->required()
                ->default('<p>Write your paragraph text here.</p>'),
        ];
    }

    public function getStyleSchema(): array
    {
        return [
            ColorPicker::make('color')
                ->label('Text Color')
                ->default('#374151'),
            Grid::make(3)->schema([
                TextInput::make('font_size')
                    ->label('Font Size (Desktop px)')
                    ->numeric()
                    ->default(16),
                TextInput::make('font_size_tablet')
                    ->label('Font Size (Tablet px)')
                    ->numeric(),
                TextInput::make('font_size_mobile')
                    ->label('Font Size (Mobile px)')
                    ->numeric(),
            ]),
            Select::make('text_align')
                ->label('Alignment')
                ->options([
                    'left' => 'Left',
                    'center' => 'Center',
                    'right' => 'Right',
                    'justify' => 'Justify',
                ])
                ->default('left'),
        ];
    }

    public function render(array $element, array $settings, string $childrenHtml = '', array $context = []): string
    {
        $rawContent = $settings['content'] ?? '';
        if (is_array($rawContent)) {
            $rawContent = \Filament\Forms\Components\RichEditor\RichContentRenderer::make($rawContent)->toHtml();
        }

        $settings['sanitized_content'] = HtmlSanitizer::clean((string) $rawContent);

        return parent::render($element, $settings, $childrenHtml, $context);
    }

    protected function getViewName(): string
    {
        return 'filament-page-builder::widgets.text';
    }
}
