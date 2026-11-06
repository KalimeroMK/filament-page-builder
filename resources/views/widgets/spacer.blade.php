@php
    $showDivider = ! empty($settings['show_divider']);
@endphp
<div
    @if($htmlId !== '') id="{{ $htmlId }}" @endif
    class="{{ $classes }} fpb-spacer"
    data-fpb-id="{{ $id }}"
    data-fpb-type="spacer"
    aria-hidden="true"
>
    @if($showDivider)
        <hr class="fpb-spacer-line" style="border-bottom: 0; border-left: 0; border-right: 0; margin: 0;" />
    @endif
</div>
