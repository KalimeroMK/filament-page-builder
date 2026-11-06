@php
    $items = is_array($settings['items'] ?? null) ? array_filter($settings['items'], 'is_array') : [];
    $networkIcons = [
        'facebook' => 'f',
        'x' => '𝕏',
        'instagram' => '◎',
        'linkedin' => 'in',
        'youtube' => '▶',
        'github' => '⌥',
    ];
@endphp
<div
    @if($htmlId !== '') id="{{ $htmlId }}" @endif
    class="{{ $classes }} fpb-social"
    data-fpb-id="{{ $id }}"
    data-fpb-type="social_icons"
    style="display: flex; gap: var(--fpb-gap, 14px); align-items: center;"
>
    @foreach($items as $item)
        @php
            $network = (string) ($item['network'] ?? 'facebook');
            $url = \KalimeroMK\FilamentPageBuilder\Support\HtmlSanitizer::sanitizeUrl((string) ($item['url'] ?? ''), '');
            if ($url === '') { continue; }
            $glyph = $networkIcons[$network] ?? '•';
        @endphp
        <a
            href="{{ $url }}"
            target="_blank"
            rel="noopener noreferrer"
            aria-label="{{ ucfirst($network) }}"
            style="display: inline-flex; align-items: center; justify-content: center; width: calc(var(--fpb-icon-size, 20px) * 2); height: calc(var(--fpb-icon-size, 20px) * 2); border-radius: 50%; background: #f1f5f9; color: var(--fpb-icon-color, #475569); font-size: var(--fpb-icon-size, 20px); font-weight: 700; text-decoration: none;"
        >
            {{ $glyph }}
        </a>
    @endforeach
</div>
