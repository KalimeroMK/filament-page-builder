@php
    $tabs = is_array($settings['tabs'] ?? null) ? array_values(array_filter($settings['tabs'], 'is_array')) : [];
@endphp
<div
    @if($htmlId !== '') id="{{ $htmlId }}" @endif
    class="{{ $classes }} fpb-tabs"
    data-fpb-id="{{ $id }}"
    data-fpb-type="tabs"
    x-data="{ activeTab: 0 }"
>
    <div class="fpb-tabs-nav" role="tablist">
        @foreach($tabs as $idx => $tab)
            <button
                type="button"
                role="tab"
                class="fpb-tabs-btn"
                x-bind:aria-selected="activeTab === {{ $idx }}"
                x-bind:class="{ 'is-active': activeTab === {{ $idx }} }"
                x-on:click="activeTab = {{ $idx }}"
            >
                {{ $tab['title'] ?? '' }}
            </button>
        @endforeach
    </div>
    <div class="fpb-tabs-panels">
        @foreach($tabs as $idx => $tab)
            <div
                class="fpb-tabs-panel"
                role="tabpanel"
                x-show="activeTab === {{ $idx }}"
                x-cloak
                style="white-space: pre-line;"
            >
                {{ $tab['content'] ?? '' }}
            </div>
        @endforeach
    </div>
</div>
