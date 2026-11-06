<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use KalimeroMK\FilamentPageBuilder\Enums\ThemeRegionType;
use KalimeroMK\FilamentPageBuilder\Services\ThemeResolver;

class NotFoundController extends Controller
{
    public function show(Request $request, ThemeResolver $themeResolver): \Illuminate\Http\Response
    {
        $template = $themeResolver->resolve(ThemeRegionType::NotFound, [
            'path' => $request->path(),
        ], $request);

        if ($template === null) {
            abort(404);
        }

        $rendered = app(\KalimeroMK\FilamentPageBuilder\Rendering\PageRenderer::class)->renderSchema(
            $template->getPublishedSchema(),
            ['path' => $request->path(), 'theme_template' => $template],
        );

        $pageModel = config('filament-page-builder.models.page', \KalimeroMK\FilamentPageBuilder\Models\Page::class);
        $page = new $pageModel();
        $page->title = $template->title;
        $page->slug = '404';
        $page->layout = 'default';
        $page->meta = ['noindex' => true];
        $page->exists = true;
        $page->setAttribute($page->getKeyName(), 0);

        return response()->view('filament-page-builder::not-found', [
            'page' => $page,
            'rendered' => $rendered,
            'isPreview' => false,
        ], 404);
    }
}
