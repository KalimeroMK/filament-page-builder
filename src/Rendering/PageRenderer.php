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
        if ($dynamicMap !== []) {
            $settings = $this->dynamicTags->resolveElementSettings($settings, $dynamicMap, $context);
        }

        event(new BeforeElementRender($element, $settings, $context));

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
                    $childParts[] = $this->renderElement($child, $context, $cssChunks);
                }
            }

            $childrenHtml = implode("\n", array_filter($childParts));
        }

        $html = $widget->render($element, $settings, $childrenHtml, $context);

        event(new AfterElementRender($element, $settings, $html, $context));

        return $html;
    }
}
