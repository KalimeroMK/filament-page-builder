<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Widgets;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use KalimeroMK\FilamentPageBuilder\Models\Menu;

class NavMenuWidget extends AbstractWidget
{
    public static function getType(): string
    {
        return 'nav_menu';
    }

    public static function getLabel(): string
    {
        return 'Nav Menu';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-bars-3';
    }

    public static function getDefaultSettings(): array
    {
        return array_merge(static::baseDefaultSettings(), [
            'menu_id' => null,
            'orientation' => 'horizontal',
            'text_color' => '#0f172a',
            'accent_color' => '#2563eb',
            'gap' => 20,
            'offcanvas_breakpoint' => 1024,
        ]);
    }

    public function getCssMap(): array
    {
        return array_merge($this->baseCssMap(), [
            'text_color' => ['var' => '--fpb-color'],
            'accent_color' => ['var' => '--fpb-accent'],
            'gap' => ['var' => '--fpb-gap', 'unit' => 'px'],
        ]);
    }

    public function getContentSchema(): array
    {
        return [
            Select::make('menu_id')
                ->label('Menu (from Menus)')
                ->options(function (): array {
                    /** @var class-string<Menu> $menuModel */
                    $menuModel = config('filament-page-builder.models.menu', Menu::class);

                    return $menuModel::query()->pluck('title', 'id')->toArray();
                })
                ->searchable()
                ->required(),
            Grid::make(2)->schema([
                Select::make('orientation')
                    ->label('Orientation')
                    ->options(['horizontal' => 'Horizontal', 'vertical' => 'Vertical'])
                    ->default('horizontal'),
                TextInput::make('offcanvas_breakpoint')
                    ->label('Mobile Menu Breakpoint (px)')
                    ->numeric()
                    ->default(1024),
            ]),
        ];
    }

    public function getStyleSchema(): array
    {
        return [
            Grid::make(3)->schema([
                ColorPicker::make('text_color')->default('#0f172a'),
                ColorPicker::make('accent_color')->default('#2563eb'),
                TextInput::make('gap')->label('Gap (px)')->numeric()->default(20),
            ]),
        ];
    }

    public function render(array $element, array $settings, string $childrenHtml = '', array $context = []): string
    {
        $menuId = (int) ($settings['menu_id'] ?? 0);
        /** @var class-string<Menu> $menuModel */
        $menuModel = config('filament-page-builder.models.menu', Menu::class);
        $settings['menu_model'] = $menuId > 0 ? $menuModel::query()->find($menuId) : null;

        return parent::render($element, $settings, $childrenHtml, $context);
    }

    protected function getViewName(): string
    {
        return 'filament-page-builder::widgets.nav-menu';
    }
}
