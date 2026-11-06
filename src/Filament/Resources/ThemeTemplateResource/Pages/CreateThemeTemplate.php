<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Filament\Resources\ThemeTemplateResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use KalimeroMK\FilamentPageBuilder\Enums\PageStatus;
use KalimeroMK\FilamentPageBuilder\Filament\Resources\ThemeTemplateResource;
use KalimeroMK\FilamentPageBuilder\Models\Page;
use KalimeroMK\FilamentPageBuilder\Models\ThemeTemplate;

class CreateThemeTemplate extends CreateRecord
{
    protected static string $resource = ThemeTemplateResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $rawJson = $data['draft_content_json'] ?? null;
        unset($data['draft_content_json']);

        $schema = Page::emptySchema();
        if (is_string($rawJson) && trim($rawJson) !== '') {
            $decoded = json_decode($rawJson, true);
            if (is_array($decoded)) {
                $schema = Page::normalizeSchema($decoded);
            }
        }

        $data['status'] = PageStatus::Draft;
        $data['draft_content'] = $schema;

        /** @var ThemeTemplate $record */
        $record = static::getModel()::create($data);

        return $record;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('builder', ['record' => $this->record]);
    }
}
