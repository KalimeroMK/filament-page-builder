@php
    $allowedTypes = ['info', 'success', 'warning', 'danger'];
    $alertType = in_array($settings['alert_type'] ?? 'info', $allowedTypes, true) ? $settings['alert_type'] : 'info';
    $isEditor = ! empty($context['is_editor']);
    $dismissible = ! isset($settings['dismissible']) || ! empty($settings['dismissible']);
@endphp
<div
    @if($htmlId !== '') id="{{ $htmlId }}" @endif
    class="{{ $classes }} fpb-alert fpb-alert--{{ $alertType }}"
    data-fpb-id="{{ $id }}"
    data-fpb-type="alert"
    role="alert"
    x-data="{ open: true }"
    x-show="open"
>
    <div class="fpb-alert-body">
        @if(!empty($settings['title']))
            <div class="fpb-alert-title">{{ $settings['title'] }}</div>
        @endif
        @if(!empty($settings['description']))
            <div class="fpb-alert-desc" style="white-space: pre-line;">{{ $settings['description'] }}</div>
        @endif
    </div>
    @if($dismissible)
        <button
            type="button"
            class="fpb-alert-close"
            @if(! $isEditor) x-on:click="open = false" @endif
            aria-label="Close"
        >×</button>
    @endif
</div>
