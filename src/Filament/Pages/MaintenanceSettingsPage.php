<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Filament\Pages;

use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use KalimeroMK\FilamentPageBuilder\FilamentPageBuilderPlugin;
use KalimeroMK\FilamentPageBuilder\Models\PageTemplate;
use KalimeroMK\FilamentPageBuilder\Models\Setting;
use KalimeroMK\FilamentPageBuilder\Support\PageBuilderAuthorizer;
use UnitEnum;

class MaintenanceSettingsPage extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected string $view = 'filament-page-builder::filament.pages.maintenance-settings';

    /**
     * @var array<string, mixed>
     */
    public array $data = [];

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-wrench-screwdriver';
    }

    public static function getNavigationLabel(): string
    {
        return 'Coming Soon';
    }

    public static function getNavigationGroup(): string | UnitEnum | null
    {
        $panel = Filament::getCurrentOrDefaultPanel();

        if ($panel !== null && $panel->hasPlugin('filament-page-builder')) {
            return FilamentPageBuilderPlugin::get()->getNavigationGroup();
        }

        return 'Page Builder';
    }

    public static function getNavigationSort(): ?int
    {
        $panel = Filament::getCurrentOrDefaultPanel();

        if ($panel !== null && $panel->hasPlugin('filament-page-builder')) {
            $base = FilamentPageBuilderPlugin::get()->getNavigationSort();

            return $base !== null ? $base + 6 : 16;
        }

        return 16;
    }

    public static function canAccess(): bool
    {
        return PageBuilderAuthorizer::can('settings');
    }

    public function getTitle(): string
    {
        return 'Coming Soon / Maintenance Mode';
    }

    public function mount(): void
    {
        $this->data = Setting::getGroup('maintenance', [
            'enabled' => false,
            'message' => 'We are currently performing scheduled maintenance. Please check back soon.',
            'template_id' => null,
            'retry_after_minutes' => 60,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Coming Soon (Maintenance Mode)')
                    ->description('When enabled, all public pages return a 503 maintenance page (optionally rendered from a Page Template). Admin, preview and Livewire stay accessible.')
                    ->schema([
                        Toggle::make('enabled')
                            ->label('Enable Coming Soon Mode'),
                        Textarea::make('message')
                            ->label('Maintenance Message')
                            ->rows(3),
                        Select::make('template_id')
                            ->label('Optional Page Template (Coming Soon)')
                            ->options(fn (): array => PageTemplate::query()->pluck('name', 'id')->toArray())
                            ->searchable()
                            ->helperText('If selected, this template renders as the 503 page (with generated CSS).'),
                        TextInput::make('retry_after_minutes')
                            ->label('Retry-After (minutes, for the Retry-After header)')
                            ->numeric()
                            ->default(60),
                    ]),
            ]);
    }

    public function save(): void
    {
        abort_unless(PageBuilderAuthorizer::can('settings'), 403);

        $state = $this->form->getState();

        Setting::setGroup('maintenance', [
            'enabled' => (bool) ($state['enabled'] ?? false),
            'message' => (string) ($state['message'] ?? ''),
            'template_id' => $state['template_id'] !== null ? (int) $state['template_id'] : null,
            'retry_after_minutes' => max(0, min(525600, (int) ($state['retry_after_minutes'] ?? 60))),
        ]);

        Notification::make()
            ->title('Coming Soon settings saved')
            ->body(! empty($state['enabled'])
                ? 'Coming Soon mode is now ENABLED for public pages.'
                : 'Coming Soon mode is disabled.')
            ->success()
            ->send();
    }
}
