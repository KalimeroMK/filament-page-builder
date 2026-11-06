<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Filament\Resources\PageResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use KalimeroMK\FilamentPageBuilder\Filament\Resources\PageResource;
use KalimeroMK\FilamentPageBuilder\Models\Page;

/**
 * @property Page $record
 */
class EditPage extends EditRecord
{
    protected static string $resource = PageResource::class;

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
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $rawJson = $data['draft_content_json'] ?? null;
        unset($data['draft_content_json']);

        if (is_string($rawJson) && trim($rawJson) !== '') {
            $decoded = json_decode($rawJson, true);
            if (is_array($decoded)) {
                $data['draft_content'] = Page::normalizeSchema($decoded);
            }
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview_draft')
                ->label('Preview Draft')
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->url(fn (): string => $this->record->getPreviewUrl(), shouldOpenInNewTab: true),

            Action::make('publish_page')
                ->label('Publish Changes')
                ->icon('heroicon-o-rocket-launch')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Publish Page')
                ->modalDescription('This will copy the current draft into the live published page and create a version snapshot.')
                ->action(function (): void {
                    $this->save(shouldRedirect: false);
                    $this->record->publish();
                    $this->refreshFormData(['status', 'published_at']);

                    Notification::make()
                        ->title('Page published to live site!')
                        ->success()
                        ->send();
                }),

            Action::make('discard_draft')
                ->label('Discard Draft')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->hasUnpublishedChanges() && $this->record->content !== null)
                ->action(function (): void {
                    $this->record->discardDraft();
                    $this->fillForm();

                    Notification::make()
                        ->title('Draft changes discarded and reset to published version.')
                        ->info()
                        ->send();
                }),

            Action::make('unpublish_page')
                ->label('Switch to Draft (Unpublish)')
                ->icon('heroicon-o-eye-slash')
                ->color('gray')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->isPublished())
                ->action(function (): void {
                    $this->record->unpublish();
                    $this->refreshFormData(['status']);

                    Notification::make()
                        ->title('Page unpublished and moved to Draft.')
                        ->warning()
                        ->send();
                }),

            DeleteAction::make(),
        ];
    }
}
