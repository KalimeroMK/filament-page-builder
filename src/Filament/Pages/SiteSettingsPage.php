<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Filament\Pages;

use Filament\Facades\Filament;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use KalimeroMK\FilamentPageBuilder\FilamentPageBuilderPlugin;
use KalimeroMK\FilamentPageBuilder\Models\PageTemplate;
use KalimeroMK\FilamentPageBuilder\Models\Setting;
use KalimeroMK\FilamentPageBuilder\Services\SiteStyleService;
use UnitEnum;

class SiteSettingsPage extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected string $view = 'filament-page-builder::filament.pages.site-settings';

    /**
     * @var array<string, mixed>
     */
    public array $data = [];

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-adjustments-horizontal';
    }

    public static function canAccess(): bool
    {
        return \KalimeroMK\FilamentPageBuilder\Support\PageBuilderAuthorizer::can('settings');
    }

    public static function getNavigationLabel(): string
    {
        return 'Site Settings';
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

            return $base !== null ? $base + 2 : 12;
        }

        return 12;
    }

    public function getTitle(): string
    {
        return 'Site Settings';
    }

    public function mount(): void
    {
        $this->data = array_merge(
            app(SiteStyleService::class)->values(),
            Setting::getGroup('maintenance', [
                'enabled' => false,
                'message' => 'We are currently performing scheduled maintenance. Please check back soon.',
                'template_id' => null,
                'retry_after_minutes' => 60,
            ]),
        );
        $this->data['custom_fonts'] = app(\KalimeroMK\FilamentPageBuilder\Services\CustomAssetsService::class)->customFonts();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Global Colors')
                    ->description('Applied site-wide through CSS variables (--fpb-global-*).')
                    ->schema([
                        Grid::make(4)->schema([
                            ColorPicker::make('primary_color')
                                ->label('Primary Color')
                                ->required(),
                            ColorPicker::make('secondary_color')
                                ->label('Secondary Color')
                                ->required(),
                            ColorPicker::make('text_color')
                                ->label('Text Color')
                                ->required(),
                            ColorPicker::make('accent_color')
                                ->label('Accent Color')
                                ->required(),
                        ]),
                    ]),
                Section::make('Global Typography')
                    ->schema([
                        Grid::make(4)->schema([
                            TextInput::make('body_font_size')
                                ->label('Body Font Size (px)')
                                ->numeric()
                                ->required(),
                            TextInput::make('h1_font_size')
                                ->label('H1 Font Size (px)')
                                ->numeric()
                                ->required(),
                            TextInput::make('h2_font_size')
                                ->label('H2 Font Size (px)')
                                ->numeric()
                                ->required(),
                            TextInput::make('h3_font_size')
                                ->label('H3 Font Size (px)')
                                ->numeric()
                                ->required(),
                        ]),
                        Select::make('font_family')
                            ->label('Font Family')
                            ->options(function (): array {
                                $options = [
                                    'system-ui, -apple-system, "Segoe UI", Roboto, sans-serif' => 'System UI (default)',
                                    'Arial, Helvetica, sans-serif' => 'Arial / Helvetica',
                                    'Georgia, "Times New Roman", serif' => 'Georgia (Serif)',
                                    '"Courier New", Courier, monospace' => 'Courier New (Monospace)',
                                ];

                                foreach (app(\KalimeroMK\FilamentPageBuilder\Services\CustomAssetsService::class)->customFontFamilies() as $family) {
                                    $options[$family] = $family . ' (custom)';
                                }

                                return $options;
                            })
                            ->required(),
                    ]),
                Section::make('Custom Fonts')
                    ->description('Register custom font families loaded from HTTPS CSS/font URLs (@font-face).')
                    ->schema([
                        Repeater::make('custom_fonts')
                            ->label('Fonts')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('family')
                                        ->label('Font Family Name')
                                        ->required(),
                                    TextInput::make('css_url')
                                        ->label('Font CSS/File URL (https)')
                                        ->required(),
                                ]),
                            ]),
                    ]),
                Section::make('Coming Soon / Maintenance Mode')
                    ->description('When enabled, public pages return a 503 maintenance page. Admin, preview and Livewire remain accessible.')
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
                            ->helperText('If selected, this template renders as the 503 page with generated CSS.'),
                        TextInput::make('retry_after_minutes')
                            ->label('Retry-After (minutes)')
                            ->numeric()
                            ->default(60),
                    ]),
            ]);
    }

    public function save(): void
    {
        abort_unless(\KalimeroMK\FilamentPageBuilder\Support\PageBuilderAuthorizer::can('settings'), 403);

        $state = $this->form->getState();

        $allowed = array_keys(SiteStyleService::defaults());
        $values = array_intersect_key($state, array_flip($allowed));

        foreach (['body_font_size', 'h1_font_size', 'h2_font_size', 'h3_font_size'] as $sizeKey) {
            if (isset($values[$sizeKey])) {
                $values[$sizeKey] = max(8, min(200, (int) $values[$sizeKey]));
            }
        }

        app(SiteStyleService::class)->save($values);

        $customFonts = $this->data['custom_fonts'] ?? [];
        if (is_array($customFonts)) {
            app(\KalimeroMK\FilamentPageBuilder\Services\CustomAssetsService::class)->saveFonts($customFonts);
        }

        Setting::setGroup('maintenance', [
            'enabled' => (bool) ($state['enabled'] ?? false),
            'message' => (string) ($state['message'] ?? ''),
            'template_id' => $state['template_id'] !== null ? (int) $state['template_id'] : null,
            'retry_after_minutes' => max(0, min(525600, (int) ($state['retry_after_minutes'] ?? 60))),
        ]);

        Notification::make()
            ->title('Site settings saved')
            ->body('Global styles and Coming Soon settings are now applied.')
            ->success()
            ->send();
    }
}
