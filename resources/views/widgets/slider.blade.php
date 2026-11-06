@php
    $slides = is_array($settings['slides'] ?? null) ? array_values(array_filter($settings['slides'], 'is_array')) : [];
    $autoplay = ! isset($settings['autoplay']) || ! empty($settings['autoplay']);
    $interval = max(1500, (int) ($settings['interval'] ?? 4000));
@endphp
<div
    @if($htmlId !== '') id="{{ $htmlId }}" @endif
    class="{{ $classes }} fpb-slider"
    data-fpb-id="{{ $id }}"
    data-fpb-type="slider"
    x-data="{
        current: 0,
        total: {{ count($slides) }},
        timer: null,
        paused: false,
        init() {
            if (@js($autoplay) && this.total > 1) {
                this.timer = setInterval(() => {
                    if (! this.paused) this.next();
                }, {{ $interval }});
            }
        },
        destroy() { clearInterval(this.timer); },
        next() { this.current = (this.current + 1) % this.total; },
        prev() { this.current = (this.current - 1 + this.total) % this.total; },
        goTo(i) { this.current = i; }
    }"
    x-on:mouseenter="paused = true"
    x-on:mouseleave="paused = false"
    x-on:focusin="paused = true"
    x-on:focusout="paused = false"
    role="region"
    aria-roledescription="carousel"
    aria-label="Image slider"
>
    <div class="fpb-slider-viewport" style="position: relative; overflow: hidden; border-radius: var(--fpb-radius, 12px);">
        @foreach($slides as $idx => $slide)
            @php
                $src = \KalimeroMK\FilamentPageBuilder\Support\HtmlSanitizer::sanitizeUrl((string) ($slide['image'] ?? ''), '');
                $link = trim((string) ($slide['link'] ?? ''));
                $safeLink = $link !== '' ? \KalimeroMK\FilamentPageBuilder\Support\HtmlSanitizer::sanitizeUrl($link, '') : '';
            @endphp
            <div class="fpb-slide" x-show="current === {{ $idx }}" @if($idx !== 0) x-cloak @endif style="position: relative;">
                @if($src !== '')
                    @if($safeLink !== '')
                        <a href="{{ $safeLink }}"><img src="{{ $src }}" alt="{{ $slide['title'] ?? '' }}" style="width: 100%; height: var(--fpb-slider-height, 400px); object-fit: cover; display: block;" loading="lazy" /></a>
                    @else
                        <img src="{{ $src }}" alt="{{ $slide['title'] ?? '' }}" style="width: 100%; height: var(--fpb-slider-height, 400px); object-fit: cover; display: block;" loading="lazy" />
                    @endif
                @endif
                @if(!empty($slide['title']) || !empty($slide['subtitle']))
                    <div style="position: absolute; left: 0; right: 0; bottom: 0; padding: 24px; background: linear-gradient(transparent, rgba(15,23,42,0.75)); color: #fff;">
                        @if(!empty($slide['title']))
                            <div style="font-size: 24px; font-weight: 700;">{{ $slide['title'] }}</div>
                        @endif
                        @if(!empty($slide['subtitle']))
                            <div style="font-size: 14px; opacity: 0.9;">{{ $slide['subtitle'] }}</div>
                        @endif
                    </div>
                @endif
            </div>
        @endforeach
        @if(count($slides) > 1)
            <button type="button" x-on:click="paused = !paused" x-bind:aria-label="paused ? 'Resume autoplay' : 'Pause autoplay'" style="position: absolute; top: 12px; right: 12px; background: rgba(15,23,42,0.6); color: #fff; border: 0; border-radius: 6px; padding: 4px 10px; font-size: 12px; cursor: pointer;" x-text="paused ? 'Play' : 'Pause'">Pause</button>
            <button type="button" x-on:click="prev()" aria-label="Previous slide" style="position: absolute; top: 50%; left: 12px; transform: translateY(-50%); background: rgba(15,23,42,0.6); color: #fff; border: 0; border-radius: 50%; width: 36px; height: 36px; cursor: pointer;">‹</button>
            <button type="button" x-on:click="next()" aria-label="Next slide" style="position: absolute; top: 50%; right: 12px; transform: translateY(-50%); background: rgba(15,23,42,0.6); color: #fff; border: 0; border-radius: 50%; width: 36px; height: 36px; cursor: pointer;">›</button>
            <div style="position: absolute; bottom: 12px; left: 0; right: 0; display: flex; justify-content: center; gap: 8px;">
                @foreach($slides as $idx => $slide)
                    <button type="button" x-on:click="goTo({{ $idx }})" x-bind:style="{ background: current === {{ $idx }} ? '#ffffff' : 'rgba(255,255,255,0.5)' }" style="width: 10px; height: 10px; border-radius: 50%; border: 0; cursor: pointer;" aria-label="Go to slide {{ $idx + 1 }}"></button>
                @endforeach
            </div>
        @endif
    </div>
</div>
