<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Filament\Resources;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use KalimeroMK\FilamentPageBuilder\Filament\Resources\CategoryResource\Pages\CreateCategory;
use KalimeroMK\FilamentPageBuilder\Filament\Resources\CategoryResource\Pages\EditCategory;
use KalimeroMK\FilamentPageBuilder\Filament\Resources\CategoryResource\Pages\ListCategories;
use KalimeroMK\FilamentPageBuilder\FilamentPageBuilderPlugin;
use KalimeroMK\FilamentPageBuilder\Models\Category;
use UnitEnum;

class CategoryResource extends Resource
{
    protected static ?string $recordTitleAttribute = 'name';

    public static function canAccess(): bool
    {
        return \KalimeroMK\FilamentPageBuilder\Support\PageBuilderAuthorizer::can('posts');
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function canCreate(): bool
    {
        return static::canAccess();
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return static::canAccess();
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return static::canAccess();
    }

    public static function getModel(): string
    {
        /** @var class-string<Category> $model */
        $model = config('filament-page-builder.models.category', Category::class);

        return $model;
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-tag';
    }

    public static function getNavigationLabel(): string
    {
        return 'Categories';
    }

    public static function getNavigationGroup(): string | UnitEnum | null
    {
        $panel = Filament::getCurrentOrDefaultPanel();

        return $panel !== null && $panel->hasPlugin('filament-page-builder')
            ? FilamentPageBuilderPlugin::get()->getNavigationGroup()
            : 'Page Builder';
    }

    public static function getNavigationSort(): ?int
    {
        $panel = Filament::getCurrentOrDefaultPanel();

        if ($panel !== null && $panel->hasPlugin('filament-page-builder')) {
            $base = FilamentPageBuilderPlugin::get()->getNavigationSort();

            return $base !== null ? $base + 3 : 13;
        }

        return 13;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(function (?string $state, Set $set, ?Category $record): void {
                    if ($record === null && filled($state)) {
                        $set('slug', Str::slug($state));
                    }
                }),
            TextInput::make('slug')
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true),
            Textarea::make('description')
                ->rows(4)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('slug')->badge()->color('gray'),
                TextColumn::make('posts_count')
                    ->label('Posts')
                    ->state(fn (Category $record): int => $record->posts()->count()),
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
            'index' => ListCategories::route('/'),
            'create' => CreateCategory::route('/create'),
            'edit' => EditCategory::route('/{record}/edit'),
        ];
    }
}
