<?php

declare(strict_types=1);

namespace KalimeroMK\FilamentPageBuilder\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use KalimeroMK\FilamentPageBuilder\Models\Setting;
use KalimeroMK\FilamentPageBuilder\Rendering\PageRenderer;

class MaintenanceMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        $settings = Setting::getGroup('maintenance', ['enabled' => false]);

        if (empty($settings['enabled'])) {
            return $next($request);
        }

        $path = trim($request->path(), '/');
        $firstSegment = explode('/', $path)[0] ?? '';

        $panelPath = 'admin';
        try {
            if (class_exists(\Filament\Facades\Filament::class)) {
                $panel = \Filament\Facades\Filament::getCurrentOrDefaultPanel();
                if ($panel !== null && method_exists($panel, 'getPath')) {
                    $panelPath = (string) ($panel->getPath() ?: 'admin');
                }
            }
        } catch (\Throwable) {
            $panelPath = 'admin';
        }

        $allowedSegments = [
            trim($panelPath, '/'),
            trim((string) config('filament-page-builder.routing.preview_prefix', '_builder/preview'), '/'),
            '_builder',
            'livewire',
        ];

        if (in_array($firstSegment, $allowedSegments, true)) {
            return $next($request);
        }

        $message = (string) ($settings['message'] ?? 'We are currently performing scheduled maintenance. Please check back soon.');

        $templateId = (int) ($settings['template_id'] ?? 0);
        if ($templateId > 0) {
            $templateModel = config('filament-page-builder.models.template', \KalimeroMK\FilamentPageBuilder\Models\PageTemplate::class);
            $template = $templateModel::query()->find($templateId);

            if ($template !== null) {
                $rendered = app(PageRenderer::class)->renderSchema($template->schema, [
                    'maintenance_message' => $message,
                ]);

                return response($rendered->toHtml(), 503, ['Content-Type' => 'text/html; charset=UTF-8']);
            }
        }

        $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Maintenance</title>'
            . '<style>body{margin:0;display:flex;align-items:center;justify-content:center;min-height:100vh;background:#0f172a;color:#e2e8f0;font-family:system-ui,sans-serif;text-align:center;padding:24px}h1{font-size:24px}p{color:#94a3b8}</style>'
            . '</head><body><div><h1>Maintenance Mode</h1><p>' . e($message) . '</p></div></body></html>';

        return response($html, 503, ['Content-Type' => 'text/html; charset=UTF-8']);
    }
}
