<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Rendering;

use KalimeroMK\FilamentPageBuilder\Events\AfterElementRender;
use KalimeroMK\FilamentPageBuilder\Events\BeforeElementRender;
use KalimeroMK\FilamentPageBuilder\Models\Page;
use KalimeroMK\FilamentPageBuilder\Registries\DynamicTagRegistry;
use KalimeroMK\FilamentPageBuilder\Registries\WidgetRegistry;
use KalimeroMK\FilamentPageBuilder\Services\PageCacheService;

class PageRenderer
{
    public function __construct(
        protected WidgetRegistry $widgets,
        protected DynamicTagRegistry $dynamicTags,
        protected CssGenerator $cssGenerator,
        protected PageCacheService $cache,
    ) {}

    /**
     * Render a Page model.
     * - When $useDraft is false, renders the published `content` and uses PageCacheService.
     * - When $useDraft is true (Editor / Live Preview), renders `draft_content` bypassing cache.
     *
     * @param  array<string, mixed>  $context
     */
    public function renderPage(Page $page, bool $useDraft = false, array $context = []): RenderedPage
    {
        $canUseCache = ! $useDraft && $context === [];

        if ($canUseCache) {
            $cached = $this->cache->get($page);

            if ($cached !== null) {
                return $cached;
            }
        }

        $schema = $useDraft
            ? $page->getWorkingSchema()
            : $page->getPublishedSchema();

        $renderContext = array_merge(['page' => $page, 'is_preview' => $useDraft], $context);

        $rendered = $this->renderSchema($schema, $renderContext);

        if ($canUseCache && $page->isPublished()) {
            $this->cache->put($page, $rendered);
        }

        return $rendered;
    }

    /**
     * Render any JSON schema tree into HTML and responsive CSS.
     *
     * @param  array<string, mixed>  $schema
     * @param  array<string, mixed>  $context
     */
    public function renderSchema(array $schema, array $context = []): RenderedPage
    {
        $normalized = Page::normalizeSchema($schema);

        /** @var array<int, string> $cssChunks */
        $cssChunks = [$this->cssGenerator->baseStyles()];
        $htmlChunks = [];

        foreach ($normalized['elements'] as $element) {
            if (! is_array($element)) {
                continue;
            }

            $htmlChunks[] = $this->renderElement($element, $context, $cssChunks);
        }

        return new RenderedPage(
            html: implode("\n", array_filter($htmlChunks)),
            css: implode("\n", array_filter($cssChunks)),
        );
    }

    /**
     * Generate only the responsive CSS for a schema tree without rendering Blade HTML views.
     * Used by the visual editor canvas to avoid duplicate widget view rendering.
     *
     * @param  array<string, mixed>  $schema
     * @param  array<string, mixed>  $context
     */
    public function generateCssForSchema(array $schema, array $context = []): string
    {
        $normalized = Page::normalizeSchema($schema);

        /** @var array<int, string> $cssChunks */
        $cssChunks = [$this->cssGenerator->baseStyles()];

        foreach ($normalized['elements'] as $element) {
            if (is_array($element)) {
                $this->collectElementCss($element, $context, $cssChunks);
            }
        }

        return implode("\n", array_filter($cssChunks));
    }

    /**
     * @param  array<string, mixed>  $element
     * @param  array<string, mixed>  $context
     * @param  array<int, string>  $cssChunks
     */
    protected function collectElementCss(array $element, array $context, array &$cssChunks): void
    {
        $type = (string) ($element['type'] ?? '');
        $id = filled($element['id'] ?? null) ? (string) $element['id'] : uniqid('el_');
        $widget = $this->widgets->get($type);

        if ($widget === null) {
            return;
        }

        $rawSettings = is_array($element['settings'] ?? null) ? $element['settings'] : [];
        $settings = array_merge($widget::getDefaultSettings(), $rawSettings);

        $dynamicMap = is_array($element['dynamic'] ?? null) ? $element['dynamic'] : [];
        $elementContext = $this->resolveElementContext($dynamicMap, $context);

        if ($dynamicMap !== []) {
            $settings = $this->dynamicTags->resolveElementSettings($settings, $dynamicMap, $elementContext);
        }

        $elementCss = $this->cssGenerator->generateForElement($id, $widget, $settings);
        if ($elementCss !== '') {
            $cssChunks[] = $elementCss;
        }

        if ($widget::isContainer() && ! empty($element['children']) && is_array($element['children'])) {
            foreach ($element['children'] as $child) {
                if (is_array($child)) {
                    $this->collectElementCss($child, $elementContext, $cssChunks);
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $element
     * @param  array<string, mixed>  $context
     * @param  array<int, string>  $cssChunks
     */
    protected function renderElement(array $element, array $context, array &$cssChunks): string
    {
        $type = (string) ($element['type'] ?? '');
        $id = filled($element['id'] ?? null) ? (string) $element['id'] : uniqid('el_');
        $element['id'] = $id;

        $widget = $this->widgets->get($type);

        if ($widget === null) {
            return '';
        }

        $rawSettings = is_array($element['settings'] ?? null) ? $element['settings'] : [];
        $settings = array_merge($widget::getDefaultSettings(), $rawSettings);

        $dynamicMap = is_array($element['dynamic'] ?? null) ? $element['dynamic'] : [];
        $elementContext = $this->resolveElementContext($dynamicMap, $context);

        if ($dynamicMap !== []) {
            $settings = $this->dynamicTags->resolveElementSettings($settings, $dynamicMap, $elementContext);
        }

        event(new BeforeElementRender($element, $settings, $elementContext));

        $id = filled($element['id'] ?? null) ? (string) $element['id'] : $id;
        $element['id'] = $id;

        if (! empty($settings['hide_element']) && empty($context['is_editor'])) {
            return '';
        }

        $elementCss = $this->cssGenerator->generateForElement($id, $widget, $settings);
        if ($elementCss !== '') {
            $cssChunks[] = $elementCss;
        }

        $childrenHtml = '';
        if ($widget::isContainer() && ! empty($element['children']) && is_array($element['children'])) {
            $childParts = [];

            foreach ($element['children'] as $child) {
                if (is_array($child)) {
                    $childParts[] = $this->renderElement($child, $elementContext, $cssChunks);
                }
            }

            $childrenHtml = implode("\n", array_filter($childParts));
        }

        $html = $widget->render($element, $settings, $childrenHtml, $elementContext);

        event(new AfterElementRender($element, $settings, $html, $elementContext));

        return $html;
    }

    /**
     * If `dynamic.model` is set on the element, resolve the current Eloquent record
     * and make it available as `record`/`model` in the render context.
     *
     * @param  array<string, mixed>  $dynamicMap
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    protected function resolveElementContext(array $dynamicMap, array $context): array
    {
        if (! array_key_exists('model', $dynamicMap)) {
            return $context;
        }

        $modelKey = $dynamicMap['model'];
        if (! is_string($modelKey) || $modelKey === '') {
            return $context;
        }

        $record = $context['record'] ?? $context['model'] ?? null;
        if ($record instanceof \Illuminate\Database\Eloquent\Model) {
            $resolved = $record->getAttribute($modelKey);
        } else {
            $page = $context['page'] ?? null;
            $resolved = $page instanceof \Illuminate\Database\Eloquent\Model
                ? $page->getAttribute($modelKey)
                : null;
        }

        if ($resolved instanceof \Illuminate\Database\Eloquent\Model) {
            $context['record'] = $resolved;
            $context['model'] = $resolved;
        } else {
            unset($context['record'], $context['model']);
        }

        return $context;
    }
}
