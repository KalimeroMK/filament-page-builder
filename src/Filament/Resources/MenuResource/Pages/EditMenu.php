<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Filament\Resources\MenuResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use KalimeroMK\FilamentPageBuilder\Filament\Resources\MenuResource;

class EditMenu extends EditRecord
{
    protected static string $resource = MenuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('menu_builder')
                ->label('Menu Builder')
                ->icon('heroicon-o-bars-3')
                ->color('primary')
                ->url(fn (): string => MenuResource::getUrl('builder', ['record' => $this->record])),
            DeleteAction::make(),
        ];
    }
}
