<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Filament\Resources\ThemeTemplateResource\Pages;

use Filament\Actions\Action;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page as ResourcePage;
use KalimeroMK\FilamentPageBuilder\Filament\Resources\ThemeTemplateResource;
use KalimeroMK\FilamentPageBuilder\Models\ThemeTemplate;

/**
 * @property ThemeTemplate $record
 */
class VisualBuilderThemeTemplate extends ResourcePage
{
    use InteractsWithRecord;

    protected static string $resource = ThemeTemplateResource::class;

    protected string $view = 'filament-page-builder::filament.pages.visual-theme-template-builder';

    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);

        $this->authorizeAccess();
    }

    public function hydrate(): void
    {
        $this->authorizeAccess();
    }

    protected function authorizeAccess(): void
    {
        abort_unless(static::getResource()::canEdit($this->getRecord()), 403);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('advanced')
                ->label('Advanced')
                ->icon('heroicon-o-code-bracket')
                ->color('gray')
                ->url(fn (): string => ThemeTemplateResource::getUrl('edit', ['record' => $this->record])),
        ];
    }

    public function getTitle(): string
    {
        return 'Visual Builder: '.$this->record->title;
    }
}
