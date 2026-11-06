<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Filament\Resources\PostResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use KalimeroMK\FilamentPageBuilder\Filament\Resources\PostResource;

class CreatePost extends CreateRecord
{
    protected static string $resource = PostResource::class;
}
