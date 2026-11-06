<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Filament\Resources;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use KalimeroMK\FilamentPageBuilder\Filament\Resources\MenuResource\Pages\CreateMenu;
use KalimeroMK\FilamentPageBuilder\Filament\Resources\MenuResource\Pages\EditMenu;
use KalimeroMK\FilamentPageBuilder\Filament\Resources\MenuResource\Pages\ListMenus;
use KalimeroMK\FilamentPageBuilder\FilamentPageBuilderPlugin;
use KalimeroMK\FilamentPageBuilder\Models\Menu;
use UnitEnum;

class MenuResource extends Resource
{
    protected static ?string $recordTitleAttribute = 'title';

    public static function canAccess(): bool
    {
        return \KalimeroMK\FilamentPageBuilder\Support\PageBuilderAuthorizer::can('menus');
    }

    public static function canCreate(): bool
    {
        return \KalimeroMK\FilamentPageBuilder\Support\PageBuilderAuthorizer::can('menus');
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return \KalimeroMK\FilamentPageBuilder\Support\PageBuilderAuthorizer::can('menus');
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return \KalimeroMK\FilamentPageBuilder\Support\PageBuilderAuthorizer::can('menus');
    }

    public static function getModel(): string
    {
        /** @var class-string<Menu> $model */
        $model = config('filament-page-builder.models.menu', Menu::class);

        return $model;
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-bars-3';
    }

    public static function canViewAny(): bool
    {
        return \KalimeroMK\FilamentPageBuilder\Support\PageBuilderAuthorizer::can('menus');
    }

    public static function getNavigationLabel(): string
    {
        return 'Menus';
    }

    public static function getNavigationGroup(): string | UnitEnum | null
    {
        $panel = Filament::getCurrentOrDefaultPanel();

        if ($panel !== null && $panel->hasPlugin('filament-page-builder')) {
            return FilamentPageBuilderPlugin::get()->getNavigationGroup();
        }

        return 'Page Builder';
    }

    public static function getNavigationSort(): ?int
    {
        $panel = Filament::getCurrentOrDefaultPanel();

        if ($panel !== null && $panel->hasPlugin('filament-page-builder')) {
            $base = FilamentPageBuilderPlugin::get()->getNavigationSort();

            return $base !== null ? $base + 4 : 14;
        }

        return 14;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                TextInput::make('title')
                    ->label('Menu Title')
                    ->required()
                    ->maxLength(255),
                TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
            ]),
            Repeater::make('items')
                ->label('Menu Items')
                ->columnSpanFull()
                ->schema([
                    Grid::make(3)->schema([
                        TextInput::make('label')->label('Label')->required(),
                        TextInput::make('url')->label('URL')->required(),
                        Select::make('target')
                            ->options(['_self' => 'Same Tab', '_blank' => 'New Tab'])
                            ->default('_self'),
                    ]),
                    Repeater::make('children')
                        ->label('Sub-items (optional dropdown)')
                        ->schema([
                            Grid::make(3)->schema([
                                TextInput::make('label')->label('Label')->required(),
                                TextInput::make('url')->label('URL')->required(),
                                Select::make('target')
                                    ->options(['_self' => 'Same Tab', '_blank' => 'New Tab'])
                                    ->default('_self'),
                            ]),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->sortable(),
                TextColumn::make('slug')->badge()->color('gray'),
                TextColumn::make('items_count')
                    ->label('Items')
                    ->state(fn (Menu $record): int => count($record->items ?? [])),
                TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMenus::route('/'),
            'create' => CreateMenu::route('/create'),
            'edit' => EditMenu::route('/{record}/edit'),
        ];
    }
}
