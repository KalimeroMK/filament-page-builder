<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Filament\Resources\CategoryResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use KalimeroMK\FilamentPageBuilder\Filament\Resources\CategoryResource;

class CreateCategory extends CreateRecord
{
    protected static string $resource = CategoryResource::class;
}
