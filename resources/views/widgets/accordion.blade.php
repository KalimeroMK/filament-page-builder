@php
    $items = is_array($settings['items'] ?? null) ? array_values(array_filter($settings['items'], 'is_array')) : [];
    $openFirst = ! isset($settings['open_first']) || ! empty($settings['open_first']);
@endphp
<div
    @if($htmlId !== '') id="{{ $htmlId }}" @endif
    class="{{ $classes }} fpb-accordion"
    data-fpb-id="{{ $id }}"
    data-fpb-type="accordion"
    x-data="{ activeIndex: {{ $openFirst ? '0' : 'null' }} }"
>
    @foreach($items as $idx => $item)
        <div class="fpb-accordion-item">
            <button
                type="button"
                class="fpb-accordion-trigger"
                x-bind:aria-expanded="activeIndex === {{ $idx }}"
                x-on:click="activeIndex = (activeIndex === {{ $idx }} ? null : {{ $idx }})"
            >
                <span>{{ $item['title'] ?? '' }}</span>
                <span x-text="activeIndex === {{ $idx }} ? '−' : '+'">+</span>
            </button>
            <div
                class="fpb-accordion-content"
                x-show="activeIndex === {{ $idx }}"
                x-cloak
                style="white-space: pre-line;"
            >
                {{ $item['content'] ?? '' }}
            </div>
        </div>
    @endforeach
</div>
