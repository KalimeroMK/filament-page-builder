<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Filament\Resources\ThemeTemplateResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use KalimeroMK\FilamentPageBuilder\Filament\Resources\ThemeTemplateResource;

class ListThemeTemplates extends ListRecords
{
    protected static string $resource = ThemeTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
