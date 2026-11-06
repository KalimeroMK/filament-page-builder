<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Http\Controllers;

use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use KalimeroMK\FilamentPageBuilder\Models\Page;
use KalimeroMK\FilamentPageBuilder\Rendering\PageRenderer;

class PagePreviewController extends Controller
{
    public function show(Request $request, string | int $page, PageRenderer $renderer): View
    {
        /** @var class-string<Page> $pageModel */
        $pageModel = config('filament-page-builder.models.page', Page::class);

        /** @var Page $pageRecord */
        $pageRecord = $pageModel::query()->findOrFail($page);

        if (! $request->hasValidSignature() && ! $this->userCanPreview($request, $pageRecord)) {
            abort(403, 'Unauthorized preview link.');
        }

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

    protected function userCanPreview(Request $request, Page $pageRecord): bool
    {
        $user = $request->user();

        if ($user === null) {
            return false;
        }

        if ($user instanceof FilamentUser) {
            $panel = Filament::getCurrentOrDefaultPanel();
            if ($panel !== null && $user->canAccessPanel($panel)) {
                return true;
            }
        }

        if (Gate::has('view-page-builder-preview')) {
            return Gate::forUser($user)->allows('view-page-builder-preview', $pageRecord);
        }

        if (Gate::getPolicyFor($pageRecord) !== null) {
            return Gate::forUser($user)->allows('view', $pageRecord);
        }

        return false;
    }
}
