@php
    $link = trim((string) ($settings['link'] ?? '#'));
    if ($link === '' || preg_match('/^\s*(javascript|vbscript|data):/i', $link)) {
        $link = '#';
    }
    $stylePreset = preg_replace('/[^a-z0-9_-]/i', '', (string) ($settings['style'] ?? 'primary')) ?: 'primary';
    $openInNewTab = ! empty($settings['open_in_new_tab']);
@endphp
<div
    @if($htmlId !== '') id="{{ $htmlId }}" @endif
    class="{{ $classes }} fpb-button-wrap"
    data-fpb-id="{{ $id }}"
    data-fpb-type="button"
>
    <a
        href="{{ $link }}"
        class="fpb-button fpb-button--{{ $stylePreset }}"
        @if($openInNewTab) target="_blank" rel="noopener noreferrer" @endif
    >
        <span>{{ $settings['label'] ?? 'Click Here' }}</span>
    </a>
</div>
