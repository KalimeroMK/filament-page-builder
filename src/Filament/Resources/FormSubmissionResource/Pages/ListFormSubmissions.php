<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Filament\Resources\FormSubmissionResource\Pages;

use Filament\Resources\Pages\ListRecords;
use KalimeroMK\FilamentPageBuilder\Filament\Resources\FormSubmissionResource;

class ListFormSubmissions extends ListRecords
{
    protected static string $resource = FormSubmissionResource::class;
}
