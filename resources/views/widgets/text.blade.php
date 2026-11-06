<div
    @if($htmlId !== '') id="{{ $htmlId }}" @endif
    class="{{ $classes }}"
    data-fpb-id="{{ $id }}"
    data-fpb-type="text"
>
    {!! $settings['sanitized_content'] ?? '' !!}
</div>
