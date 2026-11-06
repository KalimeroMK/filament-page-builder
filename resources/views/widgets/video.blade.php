@php
    $source = (string) ($settings['source'] ?? 'youtube');
    $embedUrl = (string) ($settings['resolved_embed_url'] ?? '');
    $autoplay = ! empty($settings['autoplay']);
    $mute = ! empty($settings['mute']);
    $loop = ! empty($settings['loop']);
    $controls = ! isset($settings['controls']) || ! empty($settings['controls']);
@endphp
<div
    @if($htmlId !== '') id="{{ $htmlId }}" @endif
    class="{{ $classes }}"
    data-fpb-id="{{ $id }}"
    data-fpb-type="video"
>
    <div class="fpb-video-wrap">
        @if($embedUrl !== '')
            @if($source === 'self_hosted')
                <video
                    src="{{ $embedUrl }}"
                    @if($controls) controls @endif
                    @if($autoplay) autoplay @endif
                    @if($mute) muted @endif
                    @if($loop) loop @endif
                    playsinline
                ></video>
            @else
                <iframe
                    src="{{ $embedUrl }}"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    allowfullscreen
                    loading="lazy"
                ></iframe>
            @endif
        @endif
    </div>
</div>
