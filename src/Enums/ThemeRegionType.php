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
    case NotFound = 'not_found';

    public function getLabel(): string
    {
        return match ($this) {
            self::Header => 'Header',
            self::Footer => 'Footer',
            self::Single => 'Single Post / Model',
            self::Archive => 'Archive / Listing',
            self::NotFound => '404 Not Found',
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::Header => 'primary',
            self::Footer => 'info',
            self::Single => 'success',
            self::Archive => 'warning',
            self::NotFound => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Header => 'heroicon-o-bars-3',
            self::Footer => 'heroicon-o-bars-3-bottom-right',
            self::Single => 'heroicon-o-document-text',
            self::Archive => 'heroicon-o-rectangle-stack',
            self::NotFound => 'heroicon-o-exclamation-circle',
        };
    }
}
