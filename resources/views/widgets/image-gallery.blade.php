@php
    $images = is_array($settings['images'] ?? null) ? $settings['images'] : [];
    $isEditor = ! empty($context['is_editor']);
    $lightbox = (! isset($settings['lightbox']) || ! empty($settings['lightbox'])) && ! $isEditor;
@endphp
<div
    @if($htmlId !== '') id="{{ $htmlId }}" @endif
    class="{{ $classes }} fpb-gallery"
    data-fpb-id="{{ $id }}"
    data-fpb-type="image_gallery"
    @if($lightbox)
        x-data="{ activeImage: null }"
        x-on:keydown.escape.window="activeImage = null"
    @endif
>
    <div class="fpb-gallery-grid">
        @foreach($images as $img)
            @php
                if (! is_array($img)) {
                    continue;
                }
                $rawSrc = trim((string) ($img['src'] ?? ''));
                $src = \KalimeroMK\FilamentPageBuilder\Support\HtmlSanitizer::sanitizeUrl($rawSrc, '');
                if ($src === '') {
                    continue;
                }
                $alt = (string) ($img['alt'] ?? '');
                $caption = trim((string) ($img['caption'] ?? ''));
            @endphp
            <figure class="fpb-gallery-item" style="margin: 0;">
                <img
                    src="{{ $src }}"
                    alt="{{ $alt }}"
                    class="fpb-gallery-img"
                    loading="lazy"
                    @if($lightbox)
                        x-on:click="activeImage = @js($src)"
                        style="cursor: zoom-in;"
                    @endif
                />
                @if($caption !== '')
                    <figcaption class="fpb-gallery-caption" style="margin-top: 6px; font-size: 13px; color: #64748b; text-align: center;">
                        {{ $caption }}
                    </figcaption>
                @endif
            </figure>
        @endforeach
    </div>

    @if($lightbox)
        <div
            x-show="activeImage !== null"
            x-cloak
            x-on:click="activeImage = null"
            style="position: fixed; inset: 0; z-index: 9999; background: rgba(15, 23, 42, 0.85); display: flex; align-items: center; justify-content: center; padding: 24px;"
        >
            <img x-bind:src="activeImage" alt="" style="max-width: 90vw; max-height: 90vh; border-radius: 8px;" />
        </div>
    @endif
</div>
