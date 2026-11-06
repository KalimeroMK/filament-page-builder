<div
    @if($htmlId !== '') id="{{ $htmlId }}" @endif
    class="{{ $classes }} fpb-maps"
    data-fpb-id="{{ $id }}"
    data-fpb-type="google_maps"
>
    <div style="height: var(--fpb-height, 400px); border-radius: var(--fpb-radius, 12px); overflow: hidden; background: #e2e8f0;">
        <iframe
            src="{{ $settings['embed_url'] ?? '' }}"
            width="100%"
            height="100%"
            style="border: 0; display: block;"
            allowfullscreen
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade"
            title="Google Maps"
        ></iframe>
    </div>
</div>
