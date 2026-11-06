@php
    $rawAvatar = trim((string) ($settings['avatar_url'] ?? ''));
    $avatarUrl = $rawAvatar !== '' ? \KalimeroMK\FilamentPageBuilder\Support\HtmlSanitizer::sanitizeUrl($rawAvatar, '') : '';
    $rating = max(1, min(5, (int) ($settings['rating'] ?? 5)));
@endphp
<blockquote
    @if($htmlId !== '') id="{{ $htmlId }}" @endif
    class="{{ $classes }} fpb-testimonial"
    data-fpb-id="{{ $id }}"
    data-fpb-type="testimonial"
    style="margin-left: 0; margin-right: 0;"
>
    <div class="fpb-testimonial-stars" aria-label="{{ $rating }} out of 5 stars" style="color: #f59e0b; margin-bottom: 10px; font-size: 16px;">
        {{ str_repeat('★', $rating) }}{{ str_repeat('☆', 5 - $rating) }}
    </div>
    <p class="fpb-testimonial-quote" style="font-size: 16px; font-style: italic; margin: 0 0 14px 0;">
        “{{ $settings['quote'] ?? '' }}”
    </p>
    <footer class="fpb-testimonial-footer" style="display: inline-flex; align-items: center; gap: 12px;">
        @if($avatarUrl !== '')
            <img src="{{ $avatarUrl }}" alt="{{ $settings['author_name'] ?? '' }}" style="width: 44px; height: 44px; border-radius: 50%; object-fit: cover;" loading="lazy" />
        @endif
        <div style="text-align: left;">
            <div class="fpb-testimonial-author" style="font-weight: 700; font-size: 14px;">{{ $settings['author_name'] ?? '' }}</div>
            @if(!empty($settings['author_role']))
                <div class="fpb-testimonial-role" style="font-size: 12px; opacity: 0.75;">{{ $settings['author_role'] }}</div>
            @endif
        </div>
    </footer>
</blockquote>
