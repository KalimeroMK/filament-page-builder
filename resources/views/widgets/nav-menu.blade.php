@php
    $menu = $settings['menu_model'] ?? null;
    $items = $menu?->items ?? [];
    $orientation = ($settings['orientation'] ?? 'horizontal') === 'vertical' ? 'vertical' : 'horizontal';
    $breakpoint = max(320, (int) ($settings['offcanvas_breakpoint'] ?? 1024));
    $cssId = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($cssId ?? $id)) ?: 'el';
@endphp
<nav
    @if($htmlId !== '') id="{{ $htmlId }}" @endif
    class="{{ $classes }} fpb-navmenu fpb-navmenu--{{ $orientation }}"
    data-fpb-id="{{ $id }}"
    data-fpb-type="nav_menu"
    aria-label="{{ $menu?->title ?? 'Navigation' }}"
    x-data="{ open: false }"
>
    <button
        type="button"
        class="fpb-navmenu-toggle"
        x-on:click="open = true"
        aria-label="Open menu"
        x-bind:aria-expanded="open"
        aria-controls="fpb-offcanvas-{{ $cssId }}"
        style="background: transparent; border: 0; cursor: pointer; padding: 6px; flex-direction: column; gap: 4px;"
    >
        <span style="display: block; width: 24px; height: 2px; background: var(--fpb-color, #0f172a);"></span>
        <span style="display: block; width: 24px; height: 2px; background: var(--fpb-color, #0f172a);"></span>
        <span style="display: block; width: 24px; height: 2px; background: var(--fpb-color, #0f172a);"></span>
    </button>

    <ul class="fpb-navmenu-list" style="list-style: none; margin: 0; padding: 0; display: flex; flex-direction: {{ $orientation === 'vertical' ? 'column' : 'row' }}; gap: var(--fpb-gap, 20px);">
        @foreach($items as $item)
            @php
                $url = \KalimeroMK\FilamentPageBuilder\Support\HtmlSanitizer::sanitizeUrl((string) ($item['url'] ?? ''), '');
                if ($url === '') { continue; }
                $children = $item['children'] ?? [];
            @endphp
            <li style="position: relative;" x-data="{ dropdown: false }">
                <a
                    href="{{ $url }}"
                    @if(($item['target'] ?? '_self') === '_blank') target="_blank" rel="noopener noreferrer" @endif
                    style="color: var(--fpb-color, #0f172a); text-decoration: none; font-weight: 600;"
                >
                    {{ $item['label'] }}
                </a>
                @if(!empty($children))
                    <button type="button" x-on:click="dropdown = !dropdown" aria-label="Toggle submenu" style="background: transparent; border: 0; cursor: pointer; color: var(--fpb-accent, #2563eb);">▾</button>
                    <ul x-show="dropdown" x-cloak style="list-style: none; position: absolute; left: 0; top: 100%; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px 0; min-width: 180px; box-shadow: 0 10px 24px rgba(15,23,42,0.1); z-index: 50;">
                        @foreach($children as $child)
                            @php $childUrl = \KalimeroMK\FilamentPageBuilder\Support\HtmlSanitizer::sanitizeUrl((string) ($child['url'] ?? ''), ''); @endphp
                            @if($childUrl !== '')
                                <li>
                                    <a
                                        href="{{ $childUrl }}"
                                        @if(($child['target'] ?? '_self') === '_blank') target="_blank" rel="noopener noreferrer" @endif
                                        style="display: block; padding: 8px 16px; color: var(--fpb-color, #0f172a); text-decoration: none;"
                                    >
                                        {{ $child['label'] }}
                                    </a>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                @endif
            </li>
        @endforeach
    </ul>

    {{-- Off-canvas mobile menu --}}
    <div
        x-show="open"
        x-cloak
        style="position: fixed; inset: 0; z-index: 9997; background: rgba(15,23,42,0.55);"
        x-on:click.self="open = false"
        x-on:keydown.escape.window="open = false"
        x-effect="document.body.style.overflow = open ? 'hidden' : ''"
        x-init="$watch('open', (value) => { if (value) { $nextTick(() => { const btn = $el.querySelector('.fpb-offcanvas-close'); if (btn) btn.focus(); }); } })"
        role="dialog"
        aria-modal="true"
        aria-label="Mobile navigation"
        id="fpb-offcanvas-{{ $cssId }}"
    >
        <div style="position: absolute; top: 0; left: 0; bottom: 0; width: min(85vw, 340px); background: #ffffff; padding: 24px; overflow-y: auto; box-shadow: 4px 0 24px rgba(15,23,42,0.2);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <strong>{{ $menu?->title ?? 'Menu' }}</strong>
                <button type="button" x-on:click="open = false" class="fpb-offcanvas-close" aria-label="Close menu" style="background: #f1f5f9; border: 0; border-radius: 50%; width: 30px; height: 30px; cursor: pointer;">×</button>
            </div>
            <ul style="list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 14px;">
                @foreach($items as $item)
                    @php $url = \KalimeroMK\FilamentPageBuilder\Support\HtmlSanitizer::sanitizeUrl((string) ($item['url'] ?? ''), ''); @endphp
                    @if($url !== '')
                        <li>
                            <a
                                href="{{ $url }}"
                                @if(($item['target'] ?? '_self') === '_blank') target="_blank" rel="noopener noreferrer" @endif
                                x-on:click="open = false"
                                style="display: block; padding: 8px 0; color: var(--fpb-color, #0f172a); text-decoration: none; font-weight: 600; border-bottom: 1px solid #f1f5f9;"
                            >
                                {{ $item['label'] }}
                            </a>
                            @if(!empty($item['children']))
                                <ul style="list-style: none; margin: 4px 0 0 14px; padding: 0; display: flex; flex-direction: column; gap: 6px;">
                                    @foreach($item['children'] as $child)
                                        @php $childUrl = \KalimeroMK\FilamentPageBuilder\Support\HtmlSanitizer::sanitizeUrl((string) ($child['url'] ?? ''), ''); @endphp
                                        @if($childUrl !== '')
                                            <li>
                                                <a
                                                    href="{{ $childUrl }}"
                                                    @if(($child['target'] ?? '_self') === '_blank') target="_blank" rel="noopener noreferrer" @endif
                                                    x-on:click="open = false"
                                                    style="display: block; padding: 4px 0; color: var(--fpb-accent, #2563eb); text-decoration: none; font-size: 14px;"
                                                >
                                                    {{ $child['label'] }}
                                                </a>
                                            </li>
                                        @endif
                                    @endforeach
                                </ul>
                            @endif
                        </li>
                    @endif
                @endforeach
            </ul>
        </div>
    </div>

    <style>
        @container fpb-canvas (max-width: {{ $breakpoint }}px) {
            .fpb-el-{{ $cssId }} .fpb-navmenu-list { display: none !important; }
            .fpb-el-{{ $cssId }} .fpb-navmenu-toggle { display: inline-flex !important; }
        }
        @media (max-width: {{ $breakpoint }}px) {
            .fpb-el-{{ $cssId }} .fpb-navmenu-list { display: none !important; }
            .fpb-el-{{ $cssId }} .fpb-navmenu-toggle { display: inline-flex !important; }
        }
        .fpb-el-{{ $cssId }} .fpb-navmenu-toggle { display: none; }
    </style>
</nav>
