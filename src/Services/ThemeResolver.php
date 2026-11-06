<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Services;

use Illuminate\Http\Request;
use KalimeroMK\FilamentPageBuilder\Enums\ThemeRegionType;
use KalimeroMK\FilamentPageBuilder\Models\ThemeTemplate;
use KalimeroMK\FilamentPageBuilder\Rendering\PageRenderer;
use KalimeroMK\FilamentPageBuilder\Rendering\RenderedPage;

class ThemeResolver
{
    public function __construct(
        protected ThemeConditionMatcher $matcher,
        protected PageRenderer $renderer,
    ) {}

    /**
     * Resolve the highest-priority published ThemeTemplate for `$region` whose conditions match.
     *
     * @param  array<string, mixed>  $context
     */
    public function resolve(
        string | ThemeRegionType $region,
        array $context = [],
        ?Request $request = null,
    ): ?ThemeTemplate {
        /** @var class-string<ThemeTemplate> $model */
        $model = config('filament-page-builder.models.theme_template', ThemeTemplate::class);

        $candidates = $model::query()
            ->select(['id', 'title', 'region', 'status', 'priority', 'content', 'conditions', 'published_at', 'created_at', 'updated_at'])
            ->published()
            ->forRegion($region)
            ->orderByDesc('priority')
            ->orderByDesc('id')
            ->get();

        foreach ($candidates as $template) {
            if ($this->matcher->matches($template, $context, $request)) {
                return $template;
            }
        }

        return null;
    }

    /**
     * Render the resolved ThemeTemplate for `$region` (or return an empty RenderedPage if none matches).
     *
     * @param  array<string, mixed>  $context
     */
    public function renderRegion(
        string | ThemeRegionType $region,
        array $context = [],
        ?Request $request = null,
    ): RenderedPage {
        $template = $this->resolve($region, $context, $request);

        if ($template === null) {
            return new RenderedPage(html: '', css: '');
        }

        $renderContext = array_merge($context, ['theme_template' => $template]);

        return $this->renderer->renderSchema($template->getPublishedSchema(), $renderContext);
    }
}
