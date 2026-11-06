<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Filament\Resources;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\CodeEditor;
use Filament\Forms\Components\CodeEditor\Enums\Language;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use KalimeroMK\FilamentPageBuilder\Enums\PageStatus;
use KalimeroMK\FilamentPageBuilder\Filament\Resources\PageResource\Pages\CreatePage;
use KalimeroMK\FilamentPageBuilder\Filament\Resources\PageResource\Pages\EditPage;
use KalimeroMK\FilamentPageBuilder\Filament\Resources\PageResource\Pages\ListPages;
use KalimeroMK\FilamentPageBuilder\Filament\Resources\PageResource\Pages\VisualBuilderPage;
use KalimeroMK\FilamentPageBuilder\FilamentPageBuilderPlugin;
use KalimeroMK\FilamentPageBuilder\Models\Page;
use UnitEnum;

class PageResource extends Resource
{
    protected static ?string $recordTitleAttribute = 'title';

    public static function getModel(): string
    {
        /** @var class-string<Page> $model */
        $model = config('filament-page-builder.models.page', Page::class);

        return $model;
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-document-duplicate';
    }

    public static function getNavigationLabel(): string
    {
        return 'Pages';
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
            return FilamentPageBuilderPlugin::get()->getNavigationSort();
        }

        return 10;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('page_tabs')
                ->columnSpanFull()
                ->tabs([
                    Tab::make('General & Draft Builder')
                        ->icon('heroicon-o-squares-2x2')
                        ->schema([
                            Section::make('Page Details')
                                ->schema([
                                    Grid::make(2)->schema([
                                        TextInput::make('title')
                                            ->label('Page Title')
                                            ->required()
                                            ->maxLength(255)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function (?string $state, Set $set, ?Page $record): void {
                                                if ($record === null && filled($state)) {
                                                    $set('slug', Str::slug($state));
                                                }
                                            }),
                                        TextInput::make('slug')
                                            ->label('URL Slug')
                                            ->required()
                                            ->maxLength(255)
                                            ->regex('/^[A-Za-z0-9\-_]+(?:\/[A-Za-z0-9\-_]+)*$/')
                                            ->unique(ignoreRecord: true),
                                    ]),
                                    Grid::make(2)->schema([
                                        Select::make('status')
                                            ->label('Status')
                                            ->options(PageStatus::class)
                                            ->default(PageStatus::Draft)
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->helperText('Use the Publish / Switch to Draft actions to control live publication.'),
                                        Select::make('layout')
                                            ->label('Page Layout')
                                            ->options([
                                                'default' => 'Default Theme Layout',
                                                'canvas' => 'Elementor Canvas (No Header/Footer)',
                                                'full_width' => 'Full Width',
                                            ])
                                            ->default('default')
                                            ->required(),
                                    ]),
                                ]),
                            Section::make('Draft Schema (Working Copy)')
                                ->description('All edits here are saved to draft_content first. Use Preview to inspect and Publish to push live.')
                                ->schema([
                                    CodeEditor::make('draft_content_json')
                                        ->label('Draft JSON Schema (v1)')
                                        ->language(Language::Json)
                                        ->json()
                                        ->columnSpanFull(),
                                ]),
                        ]),
                    Tab::make('SEO & Meta')
                        ->icon('heroicon-o-globe-alt')
                        ->schema([
                            Section::make('Search Engine Optimization')
                                ->schema([
                                    TextInput::make('meta.meta_title')
                                        ->label('Meta Title')
                                        ->maxLength(255),
                                    Textarea::make('meta.meta_description')
                                        ->label('Meta Description')
                                        ->rows(3),
                                    TextInput::make('meta.og_image')
                                        ->label('OpenGraph Image URL'),
                                    Toggle::make('meta.noindex')
                                        ->label('Discourage search engines from indexing this page (noindex)')
                                        ->default(false),
                                ]),
                        ]),
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
                TextColumn::make('slug')
                    ->searchable()
                    ->badge()
                    ->color('gray'),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('unpublished_indicator')
                    ->label('Draft State')
                    ->state(fn (Page $record): string => $record->hasUnpublishedChanges() ? 'Unpublished Draft' : 'Synced')
                    ->badge()
                    ->color(fn (Page $record): string => $record->hasUnpublishedChanges() ? 'warning' : 'success'),
                TextColumn::make('published_at')
                    ->label('Last Published')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(PageStatus::class),
            ])
            ->recordActions([
                Action::make('visual_builder')
                    ->label('Visual Builder')
                    ->icon('heroicon-o-squares-plus')
                    ->color('primary')
                    ->url(fn (Page $record): string => static::getUrl('builder', ['record' => $record])),
                Action::make('preview')
                    ->label('Preview Draft')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->url(fn (Page $record): string => $record->getPreviewUrl(), shouldOpenInNewTab: true),
                Action::make('publish')
                    ->label('Publish')
                    ->icon('heroicon-o-rocket-launch')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (Page $record): void {
                        $record->publish();

                        Notification::make()
                            ->title('Page published successfully')
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
            'index' => ListPages::route('/'),
            'create' => CreatePage::route('/create'),
            'edit' => EditPage::route('/{record}/edit'),
            'builder' => VisualBuilderPage::route('/{record}/builder'),
        ];
    }
}
