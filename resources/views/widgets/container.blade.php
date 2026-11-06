@php
    $allowedTags = ['div', 'section', 'header', 'footer', 'main', 'article', 'aside'];
    $tag = in_array($settings['html_tag'] ?? 'div', $allowedTags, true) ? $settings['html_tag'] : 'div';
@endphp
<{{ $tag }}
    @if($htmlId !== '') id="{{ $htmlId }}" @endif
    class="{{ $classes }}"
    data-fpb-id="{{ $id }}"
    data-fpb-type="container"
>
    {!! $childrenHtml !!}
</{{ $tag }}>
