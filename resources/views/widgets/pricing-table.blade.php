@php
    $features = is_array($settings['features'] ?? null) ? array_filter($settings['features'], 'is_array') : [];
    $highlighted = ! empty($settings['highlighted']);
    $buttonLink = \KalimeroMK\FilamentPageBuilder\Support\HtmlSanitizer::sanitizeUrl((string) ($settings['button_link'] ?? '#'), '#');
@endphp
<div
    @if($htmlId !== '') id="{{ $htmlId }}" @endif
    class="{{ $classes }} fpb-pricing"
    data-fpb-id="{{ $id }}"
    data-fpb-type="pricing_table"
    style="background: var(--fpb-bg, #ffffff); color: var(--fpb-color, #0f172a); border-radius: var(--fpb-radius, 12px); padding: 32px; text-align: center; border: 2px solid {{ $highlighted ? 'var(--fpb-accent, #2563eb)' : '#e2e8f0' }}; box-shadow: 0 4px 16px rgba(15,23,42,0.06);"
>
    @if($highlighted)
        <div style="display: inline-block; background: var(--fpb-accent, #2563eb); color: #fff; font-size: 11px; font-weight: 700; padding: 4px 12px; border-radius: 999px; margin-bottom: 12px;">FEATURED</div>
    @endif
    <div style="font-size: 20px; font-weight: 700;">{{ $settings['plan_name'] ?? '' }}</div>
    <div style="margin: 12px 0;">
        <span style="font-size: 42px; font-weight: 800; color: var(--fpb-accent, #2563eb);">{{ $settings['price'] ?? '' }}</span>
        <span style="font-size: 14px; opacity: 0.7;">{{ $settings['period'] ?? '' }}</span>
    </div>
    <ul style="list-style: none; padding: 0; margin: 18px 0; text-align: left; display: inline-block;">
        @foreach($features as $feature)
            <li style="padding: 6px 0; font-size: 14px;">✓ {{ $feature['text'] ?? '' }}</li>
        @endforeach
    </ul>
    <div>
        <a href="{{ $buttonLink }}" class="fpb-button" style="background: var(--fpb-accent, #2563eb);">{{ $settings['button_label'] ?? 'Get Started' }}</a>
    </div>
</div>
