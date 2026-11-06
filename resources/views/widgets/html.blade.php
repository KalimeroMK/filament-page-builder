<div
    @if($htmlId !== '') id="{{ $htmlId }}" @endif
    class="{{ $classes }} fpb-html-embed"
    data-fpb-id="{{ $id }}"
    data-fpb-type="html"
>
    {!! $settings['rendered_html'] ?? '' !!}
</div>
