<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Services;

use KalimeroMK\FilamentPageBuilder\Models\Page;
use KalimeroMK\FilamentPageBuilder\Registries\DynamicTagRegistry;
use KalimeroMK\FilamentPageBuilder\Registries\TemplateRegistry;
use KalimeroMK\FilamentPageBuilder\Registries\WidgetRegistry;
use KalimeroMK\FilamentPageBuilder\Rendering\PageRenderer;
use KalimeroMK\FilamentPageBuilder\Rendering\RenderedPage;

class PageBuilderManager
{
    public function __construct(
        protected WidgetRegistry $widgets,
        protected DynamicTagRegistry $dynamicTags,
        protected TemplateRegistry $templates,
        protected PageRenderer $renderer,
        protected PagePublishService $publisher,
    ) {}

    public function widgets(): WidgetRegistry
    {
        return $this->widgets;
    }

    public function dynamicTags(): DynamicTagRegistry
    {
        return $this->dynamicTags;
    }

    public function templates(): TemplateRegistry
    {
        return $this->templates;
    }

    public function publisher(): PagePublishService
    {
        return $this->publisher;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function renderPage(Page $page, bool $useDraft = false, array $context = []): RenderedPage
    {
        return $this->renderer->renderPage($page, $useDraft, $context);
    }

    /**
     * @param  array<string, mixed>  $schema
     * @param  array<string, mixed>  $context
     */
    public function renderSchema(array $schema, array $context = []): RenderedPage
    {
        return $this->renderer->renderSchema($schema, $context);
    }
}
