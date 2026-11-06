<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Filament\Resources\PageResource\Pages;

use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page as ResourcePage;
use KalimeroMK\FilamentPageBuilder\Filament\Resources\PageResource;
use KalimeroMK\FilamentPageBuilder\Models\Page;

/**
 * @property Page $record
 */
class VisualBuilderPage extends ResourcePage
{
    use InteractsWithRecord;

    protected static string $resource = PageResource::class;

    protected string $view = 'filament-page-builder::filament.pages.visual-builder-page';

    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    public function getTitle(): string
    {
        return 'Visual Builder: ' . $this->record->title;
    }
}
