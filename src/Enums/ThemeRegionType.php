<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ThemeRegionType: string implements HasColor, HasIcon, HasLabel
{
    case Header = 'header';
    case Footer = 'footer';
    case Single = 'single';
    case Archive = 'archive';

    public function getLabel(): string
    {
        return match ($this) {
            self::Header => 'Header',
            self::Footer => 'Footer',
            self::Single => 'Single Post / Model',
            self::Archive => 'Archive / Listing',
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::Header => 'primary',
            self::Footer => 'info',
            self::Single => 'success',
            self::Archive => 'warning',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Header => 'heroicon-o-bars-3',
            self::Footer => 'heroicon-o-bars-3-bottom-right',
            self::Single => 'heroicon-o-document-text',
            self::Archive => 'heroicon-o-rectangle-stack',
        };
    }
}
