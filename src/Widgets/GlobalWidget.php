<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Widgets;

use Filament\Forms\Components\Select;
use KalimeroMK\FilamentPageBuilder\Models\Page;
use KalimeroMK\FilamentPageBuilder\Models\PageTemplate;
use KalimeroMK\FilamentPageBuilder\Rendering\PageRenderer;
use KalimeroMK\FilamentPageBuilder\Support\SchemaTreeManipulator;

class GlobalWidget extends AbstractWidget
{
    public static function getType(): string
    {
        return 'global_widget';
    }

    public static function getLabel(): string
    {
        return 'Global Widget';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-globe-alt';
    }

    public static function getDefaultSettings(): array
    {
        return array_merge(static::baseDefaultSettings(), [
            'global_id' => null,
        ]);
    }

    public function getContentSchema(): array
    {
        return [
            Select::make('global_id')
                ->label('Global Template (from Template Library)')
                ->options(fn (): array => PageTemplate::query()->pluck('name', 'id')->toArray())
                ->searchable()
                ->required(),
        ];
    }

    public function getCssMap(): array
    {
        return $this->baseCssMap();
    }

    public function getStyleSchema(): array
    {
        return [];
    }

    public function render(array $element, array $settings, string $childrenHtml = '', array $context = []): string
    {
        $globalId = (int) ($settings['global_id'] ?? 0);
        $settings['global_render_error'] = null;
        $settings['global_rendered_html'] = '';

        if ($globalId <= 0) {
            $settings['global_render_error'] = 'No global template selected.';

            return parent::render($element, $settings, $childrenHtml, $context);
        }

        $stack = is_array($context['global_template_stack'] ?? null) ? $context['global_template_stack'] : [];

        if (in_array($globalId, $stack, true) || count($stack) >= 5) {
            $settings['global_render_error'] = 'Circular global widget reference detected.';

            return parent::render($element, $settings, $childrenHtml, $context);
        }

        $template = PageTemplate::query()->find($globalId);

        if ($template === null || ! is_array($template->schema)) {
            $settings['global_render_error'] = 'Global template not found.';

            return parent::render($element, $settings, $childrenHtml, $context);
        }

        $schema = Page::normalizeSchema($template->schema);
        $freshElements = array_values(array_map(
            fn (array $el): array => SchemaTreeManipulator::cloneWithFreshIds($el),
            $schema['elements'],
        ));

        $nextContext = $context;
        $nextContext['global_template_stack'] = array_merge($stack, [$globalId]);

        $rendered = app(PageRenderer::class)->renderSchema(
            ['version' => 1, 'elements' => $freshElements],
            $nextContext,
        );

        $settings['global_rendered_html'] = $rendered->toHtml();
        $settings['global_rendered_css'] = $rendered->css;

        return parent::render($element, $settings, $childrenHtml, $context);
    }

    protected function getViewName(): string
    {
        return 'filament-page-builder::widgets.global-widget';
    }
}
