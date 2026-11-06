<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $page->meta['meta_title'] ?? $page->title }}</title>
    @if(!empty($page->meta['meta_description']))
        <meta name="description" content="{{ $page->meta['meta_description'] }}">
    @endif
    @if(!empty($page->meta['og_image']))
        <meta property="og:image" content="{{ $page->meta['og_image'] }}">
    @endif
    @if(!empty($page->meta['noindex']) || !empty($isPreview))
        <meta name="robots" content="noindex, nofollow">
    @endif
    <style>
        body {
            margin: 0;
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #111827;
            background: #ffffff;
        }
        .fpb-layout-full-width {
            width: 100%;
        }
    </style>
</head>
<body>
    @includeWhen(!empty($isPreview), 'filament-page-builder::components.preview-bar', ['page' => $page])
    <main id="fpb-viewport-frame" class="fpb-layout-full-width">
        @yield('fpb-content')
    </main>
</body>
</html>
