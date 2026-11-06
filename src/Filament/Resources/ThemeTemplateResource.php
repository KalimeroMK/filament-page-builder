<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Filament\Resources;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\CodeEditor;
use Filament\Forms\Components\CodeEditor\Enums\Language;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use KalimeroMK\FilamentPageBuilder\Enums\PageStatus;
use KalimeroMK\FilamentPageBuilder\Enums\ThemeRegionType;
use KalimeroMK\FilamentPageBuilder\Filament\Resources\ThemeTemplateResource\Pages\CreateThemeTemplate;
use KalimeroMK\FilamentPageBuilder\Filament\Resources\ThemeTemplateResource\Pages\EditThemeTemplate;
use KalimeroMK\FilamentPageBuilder\Filament\Resources\ThemeTemplateResource\Pages\ListThemeTemplates;
use KalimeroMK\FilamentPageBuilder\FilamentPageBuilderPlugin;
use KalimeroMK\FilamentPageBuilder\Models\ThemeTemplate;
use UnitEnum;

class ThemeTemplateResource extends Resource
{
    protected static ?string $recordTitleAttribute = 'title';

    public static function canAccess(): bool
    {
        return \KalimeroMK\FilamentPageBuilder\Support\PageBuilderAuthorizer::can('theme_templates');
    }

    public static function canCreate(): bool
    {
        return \KalimeroMK\FilamentPageBuilder\Support\PageBuilderAuthorizer::can('theme_templates');
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return \KalimeroMK\FilamentPageBuilder\Support\PageBuilderAuthorizer::can('theme_templates');
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return \KalimeroMK\FilamentPageBuilder\Support\PageBuilderAuthorizer::can('theme_templates');
    }

    public static function getModel(): string
    {
        /** @var class-string<ThemeTemplate> $model */
        $model = config('filament-page-builder.models.theme_template', ThemeTemplate::class);

        return $model;
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-swatch';
    }

    public static function canViewAny(): bool
    {
        return \KalimeroMK\FilamentPageBuilder\Support\PageBuilderAuthorizer::can('theme_templates');
    }

    public static function getNavigationLabel(): string
    {
        return 'Theme Builder';
    }

    public static function getNavigationGroup(): string | UnitEnum | null
    {
        $panel = Filament::getCurrentOrDefaultPanel();

        if ($panel !== null && $panel->hasPlugin('filament-page-builder')) {
            return FilamentPageBuilderPlugin::get()->getNavigationGroup();
        }

        return 'Content';
    }

    public static function getNavigationSort(): ?int
    {
        $panel = Filament::getCurrentOrDefaultPanel();

        if ($panel !== null && $panel->hasPlugin('filament-page-builder')) {
            $base = FilamentPageBuilderPlugin::get()->getNavigationSort();

            return $base !== null ? $base + 1 : 11;
        }

        return 11;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Theme Template Details')
                ->columnSpanFull()
                ->schema([
                    Grid::make(3)->schema([
                        TextInput::make('title')
                            ->label('Template Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Main Site Header'),
                        Select::make('region')
                            ->label('Theme Region')
                            ->options(ThemeRegionType::class)
                            ->default(ThemeRegionType::Header)
                            ->required(),
                        TextInput::make('priority')
                            ->label('Priority (Higher wins)')
                            ->numeric()
                            ->default(10)
                            ->required(),
                    ]),
                ]),

            Section::make('Display Conditions ("Where to Display")')
                ->description('Combine Include and Exclude rules (e.g. Include Entire Site + Exclude Path checkout/*).')
                ->columnSpanFull()
                ->schema([
                    Repeater::make('conditions')
                        ->label('Condition Rules')
                        ->schema([
                            Grid::make(3)->schema([
                                Select::make('type')
                                    ->label('Rule Type')
                                    ->options([
                                        'include' => 'Include',
                                        'exclude' => 'Exclude',
                                    ])
                                    ->default('include')
                                    ->required(),
                                Select::make('target')
                                    ->label('Target')
                                    ->options([
                                        'entire_site' => 'Entire Site',
                                        'path' => 'URL Path (supports * wildcard)',
                                        'route' => 'Route Name (supports * wildcard)',
                                        'page_slug' => 'Page Slug',
                                        'model' => 'Eloquent Model Class',
                                    ])
                                    ->default('entire_site')
                                    ->required(),
                                TextInput::make('value')
                                    ->label('Pattern / Value')
                                    ->default('*')
                                    ->placeholder('e.g. *, blog/*, home, App\\Models\\Post')
                                    ->required(),
                            ]),
                        ])
                        ->default([
                            ['type' => 'include', 'target' => 'entire_site', 'value' => '*'],
                        ]),
                ]),

            Section::make('Draft Schema (Working Copy)')
                ->columnSpanFull()
                ->schema([
                    CodeEditor::make('draft_content_json')
                        ->label('Draft JSON Schema (v1)')
                        ->language(Language::Json)
                        ->json()
                        ->rule(static fn (): \Closure => function (string $attribute, mixed $value, \Closure $fail): void {
                            if (is_string($value) && trim($value) !== '' && ! is_array(json_decode($value, true))) {
                                $fail('The draft schema must be a valid JSON object.');
                            }
                        })
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('region')
                    ->badge()
                    ->sortable(),
                TextColumn::make('priority')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('published_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('region')
                    ->options(ThemeRegionType::class),
                SelectFilter::make('status')
                    ->options(PageStatus::class),
            ])
            ->recordActions([
                Action::make('publish')
                    ->label('Publish')
                    ->icon('heroicon-o-rocket-launch')
                    ->color('success')
                    ->visible(fn (ThemeTemplate $record): bool => static::canEdit($record))
                    ->authorize(fn (ThemeTemplate $record): bool => static::canEdit($record))
                    ->requiresConfirmation()
                    ->action(function (ThemeTemplate $record): void {
                        $record->publish();

                        Notification::make()
                            ->title('Theme template published')
                            ->success()
                            ->send();
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListThemeTemplates::route('/'),
            'create' => CreateThemeTemplate::route('/create'),
            'edit' => EditThemeTemplate::route('/{record}/edit'),
        ];
    }
}
