<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Widgets;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;

class FormWidget extends AbstractWidget
{
    public static function getType(): string
    {
        return 'form';
    }

    public static function getLabel(): string
    {
        return 'Form Builder';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-clipboard-document-list';
    }

    public static function getDefaultSettings(): array
    {
        return array_merge(static::baseDefaultSettings(), [
            'fields' => [
                ['name' => 'name', 'type' => 'text', 'label' => 'Your Name', 'required' => true],
                ['name' => 'email', 'type' => 'email', 'label' => 'Email Address', 'required' => true],
                ['name' => 'message', 'type' => 'textarea', 'label' => 'Message', 'required' => false],
            ],
            'submit_label' => 'Send Message',
            'actions' => [
                ['action' => 'save_to_database', 'settings' => []],
            ],
            'success_message' => 'Thank you! Your submission has been received.',
            'honeypot' => true,
            'gap' => 12,
        ]);
    }

    public function getCssMap(): array
    {
        return array_merge($this->baseCssMap(), [
            'gap' => ['var' => '--fpb-gap', 'unit' => 'px'],
        ]);
    }

    public function getContentSchema(): array
    {
        $actionOptions = ['save_to_database' => 'Save to Database'];
        foreach (app(\KalimeroMK\FilamentPageBuilder\Registries\FormActionRegistry::class)->all() as $name => $action) {
            $actionOptions[$name] = $action::getLabel();
        }

        return [
            Repeater::make('fields')
                ->label('Form Fields')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('name')
                            ->label('Field Name (key)')
                            ->required()
                            ->regex('/^[a-zA-Z0-9_]+$/'),
                        TextInput::make('label')
                            ->label('Label')
                            ->required(),
                    ]),
                    Grid::make(2)->schema([
                        Select::make('type')
                            ->label('Field Type')
                            ->options([
                                'text' => 'Text',
                                'email' => 'Email',
                                'number' => 'Number',
                                'textarea' => 'Textarea',
                                'select' => 'Select',
                                'checkbox' => 'Checkbox',
                            ])
                            ->default('text')
                            ->required(),
                        Toggle::make('required')
                            ->label('Required')
                            ->default(false),
                    ]),
                    TextInput::make('options')
                        ->label('Select Options (comma separated, for select only)')
                        ->placeholder('Option A, Option B'),
                ]),
            Repeater::make('actions')
                ->label('Actions After Submit')
                ->schema([
                    Select::make('action')
                        ->label('Action')
                        ->options($actionOptions)
                        ->required(),
                    TextInput::make('settings.to')
                        ->label('Email To (for email action)'),
                    TextInput::make('settings.subject')
                        ->label('Email Subject (for email action)'),
                    TextInput::make('settings.url')
                        ->label('URL (for redirect / webhook action)'),
                ]),
            Grid::make(2)->schema([
                TextInput::make('submit_label')
                    ->label('Submit Button Label')
                    ->default('Send Message'),
                TextInput::make('success_message')
                    ->label('Success Message')
                    ->default('Thank you! Your submission has been received.'),
            ]),
            Toggle::make('honeypot')
                ->label('Enable Honeypot Anti-Spam')
                ->default(true),
        ];
    }

    public function getStyleSchema(): array
    {
        return [
            TextInput::make('gap')
                ->label('Field Gap (px)')
                ->numeric()
                ->default(12),
        ];
    }

    public function render(array $element, array $settings, string $childrenHtml = '', array $context = []): string
    {
        $page = $context['page'] ?? null;
        $settings['form_action_url'] = $page !== null
            ? route('filament-page-builder.form.submit', ['page' => $page->getKey(), 'elementId' => $element['id'] ?? ''])
            : '#';

        return parent::render($element, $settings, $childrenHtml, $context);
    }

    protected function getViewName(): string
    {
        return 'filament-page-builder::widgets.form';
    }
}
