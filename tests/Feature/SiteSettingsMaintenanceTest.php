<?php

declare(strict_types=1);

use KalimeroMK\FilamentPageBuilder\Filament\Pages\SiteSettingsPage;
use KalimeroMK\FilamentPageBuilder\Models\Setting;
use Livewire\Livewire;

it('saves Coming Soon settings from the Site Settings page', function (): void {
    \Filament\Facades\Filament::setCurrentPanel(\Filament\Panel::make()->id('admin'));

    Livewire::test(SiteSettingsPage::class)
        ->set('data.enabled', true)
        ->set('data.message', 'Back online at noon.')
        ->set('data.retry_after_minutes', 120)
        ->call('save');

    $settings = Setting::getGroup('maintenance');
    expect($settings['enabled'])->toBeTrue()
        ->and($settings['message'])->toBe('Back online at noon.')
        ->and($settings['retry_after_minutes'])->toBe(120)
        ->and($settings['template_id'])->toBeNull();
});
