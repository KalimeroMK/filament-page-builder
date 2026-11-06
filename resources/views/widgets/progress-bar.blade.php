@php
    $percent = max(0, min(100, (float) ($settings['percent'] ?? 0)));
    $showPercent = ! isset($settings['show_percent']) || ! empty($settings['show_percent']);
@endphp
<div
    @if($htmlId !== '') id="{{ $htmlId }}" @endif
    class="{{ $classes }} fpb-progress"
    data-fpb-id="{{ $id }}"
    data-fpb-type="progress_bar"
>
    @if(!empty($settings['label']) || $showPercent)
        <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 6px;">
            <span>{{ $settings['label'] ?? '' }}</span>
            @if($showPercent)
                <strong>{{ $percent }}%</strong>
            @endif
        </div>
    @endif
    <div style="background: var(--fpb-track-color, #e2e8f0); height: var(--fpb-height, 12px); border-radius: var(--fpb-radius, 999px); overflow: hidden;"
        role="progressbar"
        aria-valuemin="0"
        aria-valuemax="100"
        aria-valuenow="{{ $percent }}"
        aria-label="{{ $settings['label'] ?? 'Progress' }}"
    >
        <div style="width: {{ $percent }}%; background: var(--fpb-bar-color, #2563eb); height: 100%; border-radius: inherit; transition: width 0.4s ease;"></div>
    </div>
</div>
