<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use KalimeroMK\FilamentPageBuilder\Models\Page;
use KalimeroMK\FilamentPageBuilder\Support\SchemaTreeManipulator;
use KalimeroMK\FilamentPageBuilder\Widgets\LoopWidget;

class LoopController extends Controller
{
    public function load(Request $request, int | string $page, string $elementId): JsonResponse
    {
        /** @var class-string<Page> $pageModel */
        $pageModel = config('filament-page-builder.models.page', Page::class);

        /** @var Page $pageRecord */
        $pageRecord = $pageModel::query()->published()->findOrFail($page);

        $schema = $pageRecord->getPublishedSchema();
        $element = SchemaTreeManipulator::findElement($schema['elements'], $elementId);

        if ($element === null || ($element['type'] ?? '') !== 'loop') {
            abort(404);
        }

        $settings = is_array($element['settings'] ?? null) ? $element['settings'] : [];
        $settings['page'] = max(1, (int) $request->query('page', 1));

        /** @var LoopWidget $widget */
        $widget = app(LoopWidget::class);
        $itemsData = $widget->renderItems($settings, ['page' => $pageRecord]);

        $itemsHtml = collect($itemsData['items'])
            ->map(fn ($item): string => '<div class="fpb-loop-item">' . $item->toHtml() . '</div>')
            ->implode("\n");

        return response()->json([
            'html' => $itemsHtml,
            'css' => $itemsData['css'],
            'has_more' => $itemsData['has_more'],
            'page' => (int) $settings['page'],
        ]);
    }
}
