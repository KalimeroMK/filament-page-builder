<div
    @if($htmlId !== '') id="{{ $htmlId }}" @endif
    class="{{ $classes }} fpb-flipbox"
    data-fpb-id="{{ $id }}"
    data-fpb-type="flip_box"
    style="height: var(--fpb-height, 220px); perspective: 1000px;"
>
    <div class="fpb-flipbox-inner" style="position: relative; width: 100%; height: 100%; transition: transform 0.6s; transform-style: preserve-3d;">
        <div class="fpb-flipbox-front" style="position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px; padding: 24px; background: var(--fpb-front-bg, #2563eb); color: var(--fpb-color, #ffffff); border-radius: var(--fpb-radius, 12px); backface-visibility: hidden; text-align: center;">
            <div style="font-size: 22px; font-weight: 700;">{{ $settings['front_title'] ?? '' }}</div>
            <div style="font-size: 14px;">{{ $settings['front_text'] ?? '' }}</div>
        </div>
        <div class="fpb-flipbox-back" style="position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px; padding: 24px; background: var(--fpb-back-bg, #0f172a); color: var(--fpb-color, #ffffff); border-radius: var(--fpb-radius, 12px); backface-visibility: hidden; transform: rotateY(180deg); text-align: center;">
            <div style="font-size: 22px; font-weight: 700;">{{ $settings['back_title'] ?? '' }}</div>
            <div style="font-size: 14px;">{{ $settings['back_text'] ?? '' }}</div>
        </div>
    </div>
</div>
