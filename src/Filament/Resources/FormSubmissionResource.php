<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Filament\Resources;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use KalimeroMK\FilamentPageBuilder\Filament\Resources\FormSubmissionResource\Pages\ListFormSubmissions;
use KalimeroMK\FilamentPageBuilder\FilamentPageBuilderPlugin;
use KalimeroMK\FilamentPageBuilder\Models\FormSubmission;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class FormSubmissionResource extends Resource
{
    protected static ?string $recordTitleAttribute = 'id';

    public static function canAccess(): bool
    {
        return \KalimeroMK\FilamentPageBuilder\Support\PageBuilderAuthorizer::can('submissions');
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return \KalimeroMK\FilamentPageBuilder\Support\PageBuilderAuthorizer::can('submissions');
    }

    public static function getModel(): string
    {
        /** @var class-string<FormSubmission> $model */
        $model = config('filament-page-builder.models.form_submission', FormSubmission::class);

        return $model;
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-inbox-arrow-down';
    }

    public static function canViewAny(): bool
    {
        return \KalimeroMK\FilamentPageBuilder\Support\PageBuilderAuthorizer::can('submissions');
    }

    public static function getNavigationLabel(): string
    {
        return 'Form Submissions';
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

            return $base !== null ? $base + 3 : 13;
        }

        return 13;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('page_id')->label('Page / Popup ID')->sortable(),
                TextColumn::make('element_id')->label('Form Element')->searchable(),
                TextColumn::make('data')
                    ->label('Submitted Data')
                    ->state(fn (FormSubmission $record): string => collect($record->data ?? [])
                        ->map(fn ($v, $k): string => $k . ': ' . (is_scalar($v) ? $v : json_encode($v)))
                        ->implode(' | '))
                    ->limit(80)
                    ->wrap(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->recordActions([
                Action::make('view_submission')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn (FormSubmission $record): string => 'Submission #' . $record->id)
                    ->modalContent(fn (FormSubmission $record): \Illuminate\Contracts\View\View => view(
                        'filament-page-builder::filament.submission-details',
                        ['record' => $record],
                    )),
                DeleteAction::make(),
            ])
            ->headerActions([
                Action::make('export_csv')
                    ->label('Export CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(fn (): StreamedResponse => static::exportCsv()),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function escapeCsvCell(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        if (preg_match('/^[\s\x00-\x1F]*[=+\-@]/', $value)) {
            return "'" . $value;
        }

        return $value;
    }

    public static function exportCsv(): StreamedResponse
    {
        $model = static::getModel();

        return response()->streamDownload(function () use ($model): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['id', 'page_id', 'element_id', 'data_json', 'actions_log_json', 'ip_address', 'created_at']);

            $model::query()->orderBy('id')->chunk(200, function ($submissions) use ($handle): void {
                foreach ($submissions as $submission) {
                    fputcsv($handle, array_map([static::class, 'escapeCsvCell'], [
                        $submission->id,
                        $submission->page_id,
                        $submission->element_id,
                        json_encode($submission->data, JSON_UNESCAPED_UNICODE),
                        json_encode($submission->actions_log, JSON_UNESCAPED_UNICODE),
                        $submission->ip_address,
                        $submission->created_at?->toDateTimeString(),
                    ]));
                }
            });

            fclose($handle);
        }, 'form-submissions-' . now()->format('Y-m-d-His') . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFormSubmissions::route('/'),
        ];
    }
}
