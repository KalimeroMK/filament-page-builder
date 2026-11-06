<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Widgets;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;

class ContainerWidget extends AbstractWidget
{
    public static function getType(): string
    {
        return 'container';
    }

    public static function getLabel(): string
    {
        return 'Flex Container';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-squares-plus';
    }

    public static function getCategory(): string
    {
        return 'layout';
    }

    public static function isContainer(): bool
    {
        return true;
    }

    public static function getDefaultSettings(): array
    {
        return array_merge(static::baseDefaultSettings(), [
            'html_tag' => 'div',
            'direction' => 'column',
            'direction_tablet' => null,
            'direction_mobile' => null,
            'wrap' => 'nowrap',
            'gap' => 20,
            'gap_tablet' => null,
            'gap_mobile' => null,
            'align_items' => 'stretch',
            'justify_content' => 'flex-start',
            'max_width' => '100%',
            'background_color' => null,
            'background_image' => '',
            'background_size' => 'cover',
            'background_position' => 'center',
            'background_repeat' => 'no-repeat',
            'background_attachment' => 'scroll',
            'border_radius' => 0,
            'padding' => 24,
        ]);
    }

    public function getCssMap(): array
    {
        return array_merge($this->baseCssMap(), [
            'direction' => ['var' => '--fpb-direction'],
            'wrap' => ['var' => '--fpb-wrap'],
            'gap' => ['var' => '--fpb-gap', 'unit' => 'px'],
            'align_items' => ['var' => '--fpb-align'],
            'justify_content' => ['var' => '--fpb-justify'],
            'max_width' => ['var' => '--fpb-max-width', 'unit' => 'px'],
            'background_color' => ['var' => '--fpb-bg'],
            'background_image' => ['var' => '--fpb-bg-image', 'format' => 'url'],
            'background_size' => ['var' => '--fpb-bg-size'],
            'background_position' => ['var' => '--fpb-bg-position'],
            'background_repeat' => ['var' => '--fpb-bg-repeat'],
            'background_attachment' => ['var' => '--fpb-bg-attachment'],
            'border_radius' => ['var' => '--fpb-radius', 'unit' => 'px'],
        ]);
    }

    public function getContentSchema(): array
    {
        $directionOptions = [
            'row' => 'Row (Horizontal)',
            'column' => 'Column (Vertical)',
            'row-reverse' => 'Row Reverse',
            'column-reverse' => 'Column Reverse',
        ];

        return [
            Grid::make(2)->schema([
                Select::make('html_tag')
                    ->label('HTML Tag')
                    ->options([
                        'div' => 'div',
                        'section' => 'section',
                        'header' => 'header',
                        'footer' => 'footer',
                        'main' => 'main',
                        'article' => 'article',
                        'aside' => 'aside',
                    ])
                    ->default('div'),
                Select::make('wrap')
                    ->label('Flex Wrap')
                    ->options([
                        'nowrap' => 'No Wrap',
                        'wrap' => 'Wrap',
                        'wrap-reverse' => 'Wrap Reverse',
                    ])
                    ->default('nowrap'),
            ]),
            Grid::make(3)->schema([
                Select::make('direction')
                    ->label('Direction (Desktop)')
                    ->options($directionOptions)
                    ->default('column'),
                Select::make('direction_tablet')
                    ->label('Direction (Tablet)')
                    ->options($directionOptions),
                Select::make('direction_mobile')
                    ->label('Direction (Mobile)')
                    ->options($directionOptions),
            ]),
            Grid::make(2)->schema([
                Select::make('align_items')
                    ->label('Align Items')
                    ->options([
                        'stretch' => 'Stretch',
                        'flex-start' => 'Start',
                        'center' => 'Center',
                        'flex-end' => 'End',
                    ])
                    ->default('stretch'),
                Select::make('justify_content')
                    ->label('Justify Content')
                    ->options([
                        'flex-start' => 'Start',
                        'center' => 'Center',
                        'flex-end' => 'End',
                        'space-between' => 'Space Between',
                        'space-around' => 'Space Around',
                    ])
                    ->default('flex-start'),
            ]),
            Grid::make(3)->schema([
                TextInput::make('gap')
                    ->label('Gap (Desktop px)')
                    ->numeric()
                    ->default(20),
                TextInput::make('gap_tablet')
                    ->label('Gap (Tablet px)')
                    ->numeric(),
                TextInput::make('gap_mobile')
                    ->label('Gap (Mobile px)')
                    ->numeric(),
            ]),
            TextInput::make('max_width')
                ->label('Max Width (e.g. 1200px or 100%)')
                ->default('100%'),
        ];
    }

    public function getStyleSchema(): array
    {
        return [
            ColorPicker::make('background_color')
                ->label('Background Color'),
            TextInput::make('background_image')
                ->label('Background Image URL')
                ->placeholder('https://example.com/background.jpg'),
            Grid::make(2)->schema([
                Select::make('background_size')
                    ->label('Background Size')
                    ->options(['cover' => 'Cover', 'contain' => 'Contain', 'auto' => 'Auto'])
                    ->default('cover'),
                Select::make('background_position')
                    ->label('Background Position')
                    ->options([
                        'center' => 'Center',
                        'top' => 'Top',
                        'bottom' => 'Bottom',
                        'left' => 'Left',
                        'right' => 'Right',
                    ])
                    ->default('center'),
            ]),
            Grid::make(2)->schema([
                Select::make('background_repeat')
                    ->label('Background Repeat')
                    ->options([
                        'no-repeat' => 'No Repeat',
                        'repeat' => 'Repeat',
                        'repeat-x' => 'Repeat Horizontally',
                        'repeat-y' => 'Repeat Vertically',
                    ])
                    ->default('no-repeat'),
                Select::make('background_attachment')
                    ->label('Background Attachment')
                    ->options(['scroll' => 'Scroll', 'fixed' => 'Fixed (Parallax)'])
                    ->default('scroll')
                    ->helperText('Fixed creates a CSS parallax effect on supporting browsers.'),
            ]),
            TextInput::make('border_radius')
                ->label('Border Radius (px)')
                ->numeric()
                ->default(0),
        ];
    }

    protected function getViewName(): string
    {
        return 'filament-page-builder::widgets.container';
    }
}
