<div
    @if($htmlId !== '') id="{{ $htmlId }}" @endif
    class="{{ $classes }} fpb-global-widget"
    data-fpb-id="{{ $id }}"
    data-fpb-type="global_widget"
>
    @if(!empty($settings['global_render_error']))
        <div style="padding: 12px; border: 1px solid #fecaca; background: #fef2f2; color: #991b1b; border-radius: 6px; font-size: 13px;">
            {{ $settings['global_render_error'] }}
        </div>
    @else
        @if(!empty($settings['global_rendered_css']))
            <style class="fpb-global-widget-styles">{!! $settings['global_rendered_css'] !!}</style>
        @endif
        {!! $settings['global_rendered_html'] !!}
    @endif
</div>
