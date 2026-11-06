<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use KalimeroMK\FilamentPageBuilder\Models\Page;
use KalimeroMK\FilamentPageBuilder\Rendering\PageRenderer;

class PagePreviewController extends Controller
{
    public function show(Request $request, string | int $page, PageRenderer $renderer): View
    {
        if (! $request->hasValidSignature() && ! $request->user()) {
            abort(403, 'Unauthorized preview link.');
        }

        /** @var class-string<Page> $pageModel */
        $pageModel = config('filament-page-builder.models.page', Page::class);

        /** @var Page $pageRecord */
        $pageRecord = $pageModel::query()->findOrFail($page);

        $rendered = $renderer->renderPage($pageRecord, useDraft: true);

        $layouts = (array) config('filament-page-builder.layouts', []);
        $layoutView = (string) ($layouts[$pageRecord->layout] ?? $layouts['default'] ?? 'filament-page-builder::layouts.default');

        return view('filament-page-builder::page', [
            'page' => $pageRecord,
            'rendered' => $rendered,
            'layoutView' => $layoutView,
            'isPreview' => true,
        ]);
    }
}
