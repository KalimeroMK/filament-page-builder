@php
    $allowedTags = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'];
    $tag = in_array($settings['tag'] ?? 'h2', $allowedTags, true) ? $settings['tag'] : 'h2';
    $link = trim((string) ($settings['link'] ?? ''));
    $isSafeLink = $link !== '' && ! preg_match('/^\s*(javascript|vbscript|data):/i', $link);
@endphp
<{{ $tag }}
    @if($htmlId !== '') id="{{ $htmlId }}" @endif
    class="{{ $classes }}"
    data-fpb-id="{{ $id }}"
    data-fpb-type="heading"
>
    @if($isSafeLink)
        <a href="{{ $link }}" class="fpb-heading-link">{{ $settings['text'] ?? '' }}</a>
    @else
        {{ $settings['text'] ?? '' }}
    @endif
</{{ $tag }}>
