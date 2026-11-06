@php
    $rating = max(0, min(5, (float) ($settings['rating'] ?? 5)));
    $fullStars = (int) floor($rating);
    $halfStar = ($rating - $fullStars) >= 0.5;
    $emptyStars = 5 - $fullStars - ($halfStar ? 1 : 0);
    $countText = trim((string) ($settings['count_text'] ?? ''));
@endphp
<div
    @if($htmlId !== '') id="{{ $htmlId }}" @endif
    class="{{ $classes }} fpb-rating"
    data-fpb-id="{{ $id }}"
    data-fpb-type="star_rating"
    style="text-align: var(--fpb-text-align, left);"
    role="img"
    aria-label="Rated {{ $rating }} out of 5 stars"
>
    <span aria-hidden="true" style="color: var(--fpb-star-color, #f59e0b); font-size: var(--fpb-star-size, 24px); letter-spacing: 2px;">
        {{ str_repeat('★', $fullStars) }}@if($halfStar)⯨@endif{{ str_repeat('☆', $emptyStars) }}
    </span>
    @if($countText !== '')
        <span style="margin-left: 8px; font-size: 13px; color: #64748b;">{{ $countText }}</span>
    @endif
</div>
