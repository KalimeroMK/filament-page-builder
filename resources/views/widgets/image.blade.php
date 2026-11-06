@php
    $rawSrc = trim((string) ($settings['src'] ?? ''));
    $src = \KalimeroMK\FilamentPageBuilder\Support\HtmlSanitizer::sanitizeUrl($rawSrc, '');
    $rawLink = trim((string) ($settings['link'] ?? ''));
    $link = $rawLink !== '' ? \KalimeroMK\FilamentPageBuilder\Support\HtmlSanitizer::sanitizeUrl($rawLink, '') : '';
    $alt = (string) ($settings['alt'] ?? '');
    $caption = trim((string) ($settings['caption'] ?? ''));
    $lazy = ! isset($settings['lazy_load']) || ! empty($settings['lazy_load']);
    $openInNewTab = ! empty($settings['open_in_new_tab']);
@endphp
<figure
    @if($htmlId !== '') id="{{ $htmlId }}" @endif
    class="{{ $classes }} fpb-image-wrap"
    data-fpb-id="{{ $id }}"
    data-fpb-type="image"
    style="margin-left: 0; margin-right: 0;"
>
    @if($src !== '')
        @if($link !== '')
            <a href="{{ $link }}" @if($openInNewTab) target="_blank" rel="noopener noreferrer" @endif>
                <img src="{{ $src }}" alt="{{ $alt }}" class="fpb-image" @if($lazy) loading="lazy" @endif />
            </a>
        @else
            <img src="{{ $src }}" alt="{{ $alt }}" class="fpb-image" @if($lazy) loading="lazy" @endif />
        @endif
    @endif
    @if($caption !== '')
        <figcaption class="fpb-image-caption" style="margin-top: 8px; font-size: 14px; color: #6b7280;">
            {{ $caption }}
        </figcaption>
    @endif
</figure>
