<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Filament\Resources\ThemeTemplateResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use KalimeroMK\FilamentPageBuilder\Filament\Resources\ThemeTemplateResource;
use KalimeroMK\FilamentPageBuilder\Models\Page;
use KalimeroMK\FilamentPageBuilder\Models\ThemeTemplate;

/**
 * @property ThemeTemplate $record
 */
class EditThemeTemplate extends EditRecord
{
    protected static string $resource = ThemeTemplateResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $schema = $this->record->getWorkingSchema();
        $data['draft_content_json'] = json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return $data;
    }

    /**
     * @param  ThemeTemplate  $record
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $rawJson = $data['draft_content_json'] ?? null;
        unset($data['draft_content_json']);

        $schema = $record->getWorkingSchema();
        if (is_string($rawJson) && trim($rawJson) !== '') {
            $decoded = json_decode($rawJson, true);
            if (is_array($decoded)) {
                $schema = Page::normalizeSchema($decoded);
            }
        }

        $record->fill($data);
        $record->saveDraft($schema);

        return $record;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('publish_template')
                ->label('Publish Theme Template')
                ->icon('heroicon-o-rocket-launch')
                ->color('success')
                ->visible(fn (): bool => ThemeTemplateResource::canEdit($this->record))
                ->authorize(fn (): bool => ThemeTemplateResource::canEdit($this->record))
                ->requiresConfirmation()
                ->action(function (): void {
                    $data = $this->form->getState();
                    $this->handleRecordUpdate($this->record, $data);
                    $this->record->publish();

                    Notification::make()
                        ->title('Theme template published!')
                        ->success()
                        ->send();
                }),

            Action::make('unpublish_template')
                ->label('Switch to Draft (Unpublish)')
                ->icon('heroicon-o-eye-slash')
                ->color('gray')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->isPublished() && ThemeTemplateResource::canEdit($this->record))
                ->authorize(fn (): bool => ThemeTemplateResource::canEdit($this->record))
                ->action(function (): void {
                    $this->record->unpublish();

                    Notification::make()
                        ->title('Theme template unpublished.')
                        ->warning()
                        ->send();
                }),

            DeleteAction::make(),
        ];
    }
}
