<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;
use KalimeroMK\FilamentPageBuilder\Models\Page;
use KalimeroMK\FilamentPageBuilder\Rendering\PageRenderer;

class PageController extends Controller
{
    public function show(string $slug, PageRenderer $renderer): View
    {
        /** @var class-string<Page> $pageModel */
        $pageModel = config('filament-page-builder.models.page', Page::class);

        /** @var Page $page */
        $page = $pageModel::query()
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        $rendered = $renderer->renderPage($page, useDraft: false);

        $layouts = (array) config('filament-page-builder.layouts', []);
        $layoutView = (string) ($layouts[$page->layout] ?? $layouts['default'] ?? 'filament-page-builder::layouts.default');

        return view('filament-page-builder::page', [
            'page' => $page,
            'rendered' => $rendered,
            'layoutView' => $layoutView,
            'isPreview' => false,
        ]);
    }
}
