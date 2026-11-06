<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Widgets;

use Filament\Forms\Components\CodeEditor;
use Filament\Forms\Components\CodeEditor\Enums\Language;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use KalimeroMK\FilamentPageBuilder\Models\Page;
use KalimeroMK\FilamentPageBuilder\Models\PageTemplate;
use KalimeroMK\FilamentPageBuilder\Rendering\PageRenderer;
use KalimeroMK\FilamentPageBuilder\Services\LoopQueryBuilder;
use KalimeroMK\FilamentPageBuilder\Support\SchemaTreeManipulator;

class LoopWidget extends AbstractWidget
{
    public static function getType(): string
    {
        return 'loop';
    }

    public static function getLabel(): string
    {
        return 'Loop Builder';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-arrow-path-rounded-square';
    }

    public static function isContainer(): bool
    {
        return true;
    }

    public static function getDefaultSettings(): array
    {
        return array_merge(static::baseDefaultSettings(), [
            'model_class' => '',
            'scope' => '',
            'limit' => 6,
            'order_by' => 'id',
            'order_direction' => 'desc',
            'filters' => [],
            'template_id' => null,
            'item_schema_json' => '',
            'layout' => 'grid',
            'columns' => 3,
            'gap' => 20,
            'pagination' => 'none',
            'page_size' => 6,
        ]);
    }

    public function getCssMap(): array
    {
        return array_merge($this->baseCssMap(), [
            'columns' => ['var' => '--fpb-loop-cols'],
            'gap' => ['var' => '--fpb-gap', 'unit' => 'px'],
        ]);
    }

    public function getContentSchema(): array
    {
        $templateOptions = PageTemplate::query()->pluck('name', 'id')->toArray();

        return [
            TextInput::make('model_class')
                ->label('Eloquent Model Class (FQCN)')
                ->placeholder('App\\Models\\Post')
                ->required(),
            Grid::make(3)->schema([
                TextInput::make('limit')
                    ->label('Limit')
                    ->numeric()
                    ->default(6)
                    ->required(),
                TextInput::make('order_by')
                    ->label('Order By (column)')
                    ->default('id'),
                Select::make('order_direction')
                    ->label('Direction')
                    ->options(['asc' => 'Ascending', 'desc' => 'Descending'])
                    ->default('desc'),
            ]),
            TextInput::make('scope')
                ->label('Optional Eloquent Scope (e.g. published)'),
            Repeater::make('filters')
                ->label('Query Filters (where)')
                ->schema([
                    Grid::make(3)->schema([
                        TextInput::make('field')->label('Field')->required(),
                        Select::make('operator')
                            ->options([
                                '=' => '=',
                                '!=' => '!=',
                                '<' => '<',
                                '<=' => '<=',
                                '>' => '>',
                                '>=' => '>=',
                                'like' => 'LIKE',
                            ])
                            ->default('='),
                        TextInput::make('value')->label('Value')->required(),
                    ]),
                ]),
            Select::make('template_id')
                ->label('Sub-template from Template Library (optional)')
                ->options($templateOptions)
                ->searchable(),
            CodeEditor::make('item_schema_json')
                ->label('Inline Item JSON Schema (optional, overrides Template Library)')
                ->language(Language::Json)
                ->json()
                ->rule(static fn (): \Closure => function (string $attribute, mixed $value, \Closure $fail): void {
                    if (is_string($value) && trim($value) !== '' && ! is_array(json_decode($value, true))) {
                        $fail('The item schema must be a valid JSON object.');
                    }
                })
                ->helperText('Schema to render for EACH record. Dynamic tag "model.attribute" resolves against the current record.'),
            Grid::make(2)->schema([
                Select::make('layout')
                    ->label('Loop Layout')
                    ->options(['grid' => 'Grid', 'list' => 'List', 'carousel' => 'Carousel'])
                    ->default('grid'),
                TextInput::make('columns')
                    ->label('Grid Columns')
                    ->numeric()
                    ->default(3),
            ]),
            TextInput::make('gap')
                ->label('Gap (px)')
                ->numeric()
                ->default(20),
            Select::make('pagination')
                ->label('Pagination')
                ->options(['none' => 'None', 'load_more' => 'Load More (AJAX)'])
                ->default('none'),
            TextInput::make('page_size')
                ->label('Items Per Page')
                ->numeric()
                ->default(6),
        ];
    }

    public function getStyleSchema(): array
    {
        return [];
    }

    public function render(array $element, array $settings, string $childrenHtml = '', array $context = []): string
    {
        $settings['loop_config_error'] = null;
        $settings['rendered_loop_items'] = [];
        $settings['loop_items_css'] = '';

        $depth = (int) ($context['loop_depth'] ?? 0);
        $stack = is_array($context['loop_template_stack'] ?? null) ? $context['loop_template_stack'] : [];

        if ($depth >= 5) {
            $settings['loop_config_error'] = 'Maximum nested loop depth reached.';

            return parent::render($element, $settings, $childrenHtml, $context);
        }

        $itemSchema = $this->resolveItemSchema($settings, $stack);

        if ($itemSchema === null) {
            $settings['loop_config_error'] = 'Invalid item schema JSON or circular template reference.';

            return parent::render($element, $settings, $childrenHtml, $context);
        }

        $itemsData = $this->renderItems($settings, $context, $itemSchema);

        $settings['rendered_loop_items'] = $itemsData['items'];
        $settings['loop_items_css'] = $itemsData['css'];
        $settings['loop_has_more'] = $itemsData['has_more'];

        return parent::render($element, $settings, $childrenHtml, $context);
    }

    /**
     * Render loop items for the current page and determine whether more records exist.
     *
     * @param  array<string, mixed>  $settings
     * @param  array<string, mixed>  $context
     * @param  array{version: int, elements: array<int, array<string, mixed>>}|null  $itemSchema
     * @return array{items: array<int, \KalimeroMK\FilamentPageBuilder\Rendering\RenderedPage>, css: string, has_more: bool}
     */
    public function renderItems(array $settings, array $context = [], ?array $itemSchema = null): array
    {
        if ($itemSchema === null) {
            $stack = is_array($context['loop_template_stack'] ?? null) ? $context['loop_template_stack'] : [];
            $itemSchema = $this->resolveItemSchema($settings, $stack);

            if ($itemSchema === null) {
                return ['items' => [], 'css' => '', 'has_more' => false];
            }
        }

        $records = app(LoopQueryBuilder::class)->query($settings);

        if ($records->isEmpty()) {
            return ['items' => [], 'css' => '', 'has_more' => false];
        }

        $renderer = app(PageRenderer::class);
        $renderedItems = [];
        $cssParts = [];
        $depth = (int) ($context['loop_depth'] ?? 0);
        $stack = is_array($context['loop_template_stack'] ?? null) ? $context['loop_template_stack'] : [];

        $nextContext = $context;
        $nextContext['loop_depth'] = $depth + 1;
        $nextContext['loop_template_stack'] = $stack;
        if (is_numeric($settings['template_id'] ?? null)) {
            $nextContext['loop_template_stack'][] = (int) $settings['template_id'];
        }

        foreach ($records as $record) {
            $freshElements = array_values(array_map(
                fn (array $el): array => SchemaTreeManipulator::cloneWithFreshIds($el),
                $itemSchema['elements'],
            ));

            $itemContext = array_merge($nextContext, [
                'record' => $record,
                'model' => $record,
                'is_loop_item' => true,
            ]);

            $rendered = $renderer->renderSchema(['version' => 1, 'elements' => $freshElements], $itemContext);
            $renderedItems[] = $rendered;
            if ($rendered->css !== '') {
                $cssParts[] = $rendered->css;
            }
        }

        return [
            'items' => $renderedItems,
            'css' => implode("\n", $cssParts),
            'has_more' => app(LoopQueryBuilder::class)->hasMore($settings),
        ];
    }

    /**
     * @param  array<string, mixed>  $settings
     * @param  array<int, int>  $stack
     * @return array{version: int, elements: array<int, array<string, mixed>>}|null
     */
    protected function resolveItemSchema(array $settings, array $stack): ?array
    {
        $inlineJson = trim((string) ($settings['item_schema_json'] ?? ''));
        if ($inlineJson !== '') {
            $decoded = json_decode($inlineJson, true);
            if (! is_array($decoded)) {
                return null;
            }

            if (isset($decoded['elements']) && is_array($decoded['elements'])) {
                return Page::normalizeSchema($decoded);
            }

            if (array_is_list($decoded)) {
                return Page::normalizeSchema(['elements' => $decoded]);
            }

            return null;
        }

        $templateId = $settings['template_id'] ?? null;
        if ($templateId !== null && is_numeric($templateId)) {
            $id = (int) $templateId;
            if (in_array($id, $stack, true)) {
                return null;
            }

            $template = PageTemplate::query()->find($id);
            if ($template !== null && is_array($template->schema)) {
                return Page::normalizeSchema($template->schema);
            }

            return null;
        }

        // Default fallback: a simple container with a dynamic title heading
        return [
            'version' => 1,
            'elements' => [
                [
                    'id' => 'loop_default_heading',
                    'type' => 'heading',
                    'settings' => ['text' => '', 'tag' => 'h3'],
                    'dynamic' => ['text' => 'model.attribute:title'],
                ],
            ],
        ];
    }

    protected function getViewName(): string
    {
        return 'filament-page-builder::widgets.loop';
    }
}
