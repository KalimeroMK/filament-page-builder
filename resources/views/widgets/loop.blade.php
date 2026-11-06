@php
    $layout = in_array(($settings['layout'] ?? 'grid'), ['grid', 'list'], true) ? $settings['layout'] : 'grid';
    $renderedItems = $settings['rendered_loop_items'] ?? [];
    $loopCss = (string) ($settings['loop_items_css'] ?? '');
    $configError = $settings['loop_config_error'] ?? null;
@endphp
<div
    @if($htmlId !== '') id="{{ $htmlId }}" @endif
    class="{{ $classes }} fpb-loop fpb-loop--{{ $layout }}"
    data-fpb-id="{{ $id }}"
    data-fpb-type="loop"
>
    @if($configError !== null)
        <div style="padding: 12px; border: 1px solid #fecaca; background: #fef2f2; color: #991b1b; border-radius: 6px; font-size: 13px;">
            {{ $configError }}
        </div>
    @endif

    @if($loopCss !== '')
        <style class="fpb-loop-item-styles">{!! $loopCss !!}</style>
    @endif

    <div class="fpb-loop-items">
        @foreach($renderedItems as $item)
            <div class="fpb-loop-item">
                {!! $item->toHtml() !!}
            </div>
        @endforeach
        @if($renderedItems === [] && ! empty($context['is_editor']) && $configError === null)
            <div style="padding: 16px; border: 1px dashed #cbd5e1; border-radius: 6px; text-align: center; color: #94a3b8; font-size: 12px;">
                No records found or model class not allowed. Configure the Loop Builder settings and allow the model in config.
            </div>
        @endif
    </div>

    @if(trim($childrenHtml) !== '')
        <div class="fpb-loop-children">
            {!! $childrenHtml !!}
        </div>
    @endif
</div>
