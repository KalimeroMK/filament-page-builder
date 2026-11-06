<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Filament\Resources;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use KalimeroMK\FilamentPageBuilder\Filament\Resources\PostResource\Pages\CreatePost;
use KalimeroMK\FilamentPageBuilder\Filament\Resources\PostResource\Pages\EditPost;
use KalimeroMK\FilamentPageBuilder\Filament\Resources\PostResource\Pages\ListPosts;
use KalimeroMK\FilamentPageBuilder\FilamentPageBuilderPlugin;
use KalimeroMK\FilamentPageBuilder\Models\Post;
use UnitEnum;

class PostResource extends Resource
{
    protected static ?string $recordTitleAttribute = 'title';

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
        /** @var class-string<Post> $model */
        $model = config('filament-page-builder.models.post', Post::class);

        return $model;
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-newspaper';
    }

    public static function getNavigationLabel(): string
    {
        return 'Posts';
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

            return $base !== null ? $base + 2 : 12;
        }

        return 12;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (?string $state, Set $set, ?Post $record): void {
                        if ($record === null && filled($state)) {
                            $set('slug', Str::slug($state));
                        }
                    }),
                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
            ]),
            Grid::make(2)->schema([
                Select::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                TextInput::make('author')->maxLength(255),
            ]),
            Grid::make(2)->schema([
                Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                        'scheduled' => 'Scheduled',
                        'archived' => 'Archived',
                    ])
                    ->default('draft')
                    ->required(),
                DateTimePicker::make('published_at')
                    ->label('Publish At')
                    ->helperText('Required for published posts. Future dates are used for scheduled posts.'),
            ]),
            FileUpload::make('image')
                ->label('Featured Image')
                ->image()
                ->disk('public')
                ->directory('fpb/posts')
                ->visibility('public')
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif'])
                ->maxSize(5120)
                ->helperText('Upload a JPEG, PNG, WebP or GIF image up to 5 MB.')
                ->columnSpanFull(),
            Textarea::make('excerpt')
                ->rows(3)
                ->columnSpanFull(),
            RichEditor::make('body')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->sortable(),
                TextColumn::make('category.name')->badge()->color('gray')->sortable(),
                TextColumn::make('author')->searchable()->toggleable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('published_at')->dateTime()->sortable(),
                TextColumn::make('updated_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'draft' => 'Draft',
                    'published' => 'Published',
                    'scheduled' => 'Scheduled',
                    'archived' => 'Archived',
                ]),
                SelectFilter::make('category_id')->relationship('category', 'name'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('published_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPosts::route('/'),
            'create' => CreatePost::route('/create'),
            'edit' => EditPost::route('/{record}/edit'),
        ];
    }
}
