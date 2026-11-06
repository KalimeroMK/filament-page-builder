@php
    $iconName = preg_replace('/[^a-z0-9_-]/i', '', (string) ($settings['icon'] ?? 'heroicon-o-star')) ?: 'heroicon-o-star';
    $rawLink = trim((string) ($settings['link'] ?? ''));
    $link = $rawLink !== '' ? \KalimeroMK\FilamentPageBuilder\Support\HtmlSanitizer::sanitizeUrl($rawLink, '') : '';
    $openInNewTab = ! empty($settings['open_in_new_tab']);
    try {
        $svgHtml = svg($iconName)->toHtml();
    } catch (\Throwable) {
        $svgHtml = svg('heroicon-o-star')->toHtml();
    }
@endphp
<div
    @if($htmlId !== '') id="{{ $htmlId }}" @endif
    class="{{ $classes }} fpb-icon-wrap"
    data-fpb-id="{{ $id }}"
    data-fpb-type="icon"
>
    @if($link !== '')
        <a href="{{ $link }}" class="fpb-icon-box" @if($openInNewTab) target="_blank" rel="noopener noreferrer" @endif>
            {!! $svgHtml !!}
        </a>
    @else
        <span class="fpb-icon-box">
            {!! $svgHtml !!}
        </span>
    @endif
</div>
