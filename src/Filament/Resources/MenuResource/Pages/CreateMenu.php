<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Filament\Resources\MenuResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use KalimeroMK\FilamentPageBuilder\Filament\Resources\MenuResource;

class CreateMenu extends CreateRecord
{
    protected static string $resource = MenuResource::class;
}
