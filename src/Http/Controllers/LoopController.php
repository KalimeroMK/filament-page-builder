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

        // Optional AJAX filter (validated against exposed ajax_filters or allowed_fields config)
        $filterField = trim((string) $request->query('filter_field', ''));
        $filterValue = $request->query('filter_value');

        if ($filterField !== '' && is_scalar($filterValue)) {
            $exposedFilters = $settings['ajax_filters'] ?? [];
            if (is_string($exposedFilters)) {
                $exposedFilters = array_filter(array_map('trim', explode(',', $exposedFilters)));
            }
            if (! is_array($exposedFilters)) {
                $exposedFilters = [];
            }

            $allowedByConfig = (array) config('filament-page-builder.loop.allowed_fields', []);
            $modelClass = (string) ($settings['model_class'] ?? '');
            $modelAllowedFields = $allowedByConfig[$modelClass] ?? $allowedByConfig['*'] ?? [];

            // Fail closed: only explicitly exposed fields can be filtered via AJAX
            $isExposed = $exposedFilters !== []
                ? in_array($filterField, $exposedFilters, true)
                : ($modelAllowedFields !== [] && in_array($filterField, $modelAllowedFields, true));

            if (! $isExposed || ! preg_match('/^[a-zA-Z0-9_.]+$/', $filterField)) {
                return response()->json([
                    'message' => 'The requested filter field is not allowed.',
                ], 422);
            }

            $settings['filters'] = array_values(array_merge(
                is_array($settings['filters'] ?? null) ? $settings['filters'] : [],
                [['field' => $filterField, 'operator' => '=', 'value' => $filterValue]],
            ));
        }

        /** @var LoopWidget $widget */
        $widget = app(LoopWidget::class);
        $itemsData = $widget->renderItems($settings, ['page' => $pageRecord]);

        $itemsHtml = collect($itemsData['items'])
            ->map(fn ($item): string => '<div class="fpb-loop-item">' . $item->toHtml() . '</div>')
            ->implode("\n");

        $pageSize = app(\KalimeroMK\FilamentPageBuilder\Services\LoopQueryBuilder::class)->effectivePageSize($settings);
        $total = app(\KalimeroMK\FilamentPageBuilder\Services\LoopQueryBuilder::class)->total($settings);
        $totalPages = $total > 0 ? (int) ceil($total / $pageSize) : 0;

        return response()->json([
            'html' => $itemsHtml,
            'css' => $itemsData['css'],
            'has_more' => $itemsData['has_more'],
            'page' => (int) $settings['page'],
            'total' => $total,
            'total_pages' => $totalPages,
        ]);
    }
}
