@php
    $buttonLink = \KalimeroMK\FilamentPageBuilder\Support\HtmlSanitizer::sanitizeUrl((string) ($settings['button_link'] ?? '#'), '#');
@endphp
<div
    @if($htmlId !== '') id="{{ $htmlId }}" @endif
    class="{{ $classes }} fpb-cta"
    data-fpb-id="{{ $id }}"
    data-fpb-type="cta"
    style="background: var(--fpb-bg, #2563eb); color: var(--fpb-color, #ffffff); border-radius: var(--fpb-radius, 12px); text-align: var(--fpb-text-align, center);"
>
    <div style="font-size: 30px; font-weight: 800; margin-bottom: 10px;">{{ $settings['title'] ?? '' }}</div>
    @if(!empty($settings['subtitle']))
        <div style="font-size: 16px; opacity: 0.92; margin-bottom: 20px; white-space: pre-line;">{{ $settings['subtitle'] }}</div>
    @endif
    <a href="{{ $buttonLink }}" class="fpb-button fpb-button--dark">{{ $settings['button_label'] ?? 'Get Started' }}</a>
</div>
