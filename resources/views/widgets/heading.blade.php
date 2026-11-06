@php
    $allowedTags = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'];
    $tag = in_array($settings['tag'] ?? 'h2', $allowedTags, true) ? $settings['tag'] : 'h2';
    $rawLink = trim((string) ($settings['link'] ?? ''));
    $safeLink = $rawLink !== ''
        ? \KalimeroMK\FilamentPageBuilder\Support\HtmlSanitizer::sanitizeUrl($rawLink, '')
        : '';
@endphp
<{{ $tag }}
    @if($htmlId !== '') id="{{ $htmlId }}" @endif
    class="{{ $classes }}"
    data-fpb-id="{{ $id }}"
    data-fpb-type="heading"
>
    @if($safeLink !== '')
        <a href="{{ $safeLink }}" class="fpb-heading-link">{{ $settings['text'] ?? '' }}</a>
    @else
        {{ $settings['text'] ?? '' }}
    @endif
</{{ $tag }}>
