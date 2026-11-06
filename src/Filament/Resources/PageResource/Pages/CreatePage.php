<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Filament\Resources\PageResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use KalimeroMK\FilamentPageBuilder\Filament\Resources\PageResource;
use KalimeroMK\FilamentPageBuilder\Models\Page;

class CreatePage extends CreateRecord
{
    protected static string $resource = PageResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $rawJson = $data['draft_content_json'] ?? null;
        unset($data['draft_content_json']);

        if (is_string($rawJson) && trim($rawJson) !== '') {
            $decoded = json_decode($rawJson, true);
            $data['draft_content'] = is_array($decoded) ? Page::normalizeSchema($decoded) : Page::emptySchema();
        } else {
            $data['draft_content'] = Page::emptySchema();
        }

        return $data;
    }
}
