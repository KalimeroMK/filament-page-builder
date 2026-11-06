<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Filament\Resources\PageResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use KalimeroMK\FilamentPageBuilder\Enums\PageStatus;
use KalimeroMK\FilamentPageBuilder\Filament\Resources\PageResource;
use KalimeroMK\FilamentPageBuilder\Models\Page;

class CreatePage extends CreateRecord
{
    protected static string $resource = PageResource::class;

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

        /** @var Page $record */
        $record = static::getModel()::create($data);
        $record->saveDraft($schema, is_array($data['meta'] ?? null) ? $data['meta'] : null);

        return $record;
    }
}
