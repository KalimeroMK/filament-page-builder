@php
    if (! empty($isPreview)) {
        return;
    }

    $popupResolver = app(\KalimeroMK\FilamentPageBuilder\Services\PopupResolver::class);
    $activePopups = $popupResolver->activePopups(['page' => $page ?? null]);
    $renderer = app(\KalimeroMK\FilamentPageBuilder\Rendering\PageRenderer::class);
@endphp
@if($activePopups->isNotEmpty() && empty($isPreview))
    @foreach($activePopups as $popup)
        @php
            $trigger = $popup->trigger_settings ?? [];
            $triggerType = (string) ($trigger['type'] ?? 'on_load');
            $delaySeconds = max(0, (int) ($trigger['delay_seconds'] ?? 2));
            $scrollPercent = max(1, min(100, (int) ($trigger['scroll_percent'] ?? 50)));
            $maxWidth = max(280, min(1200, (int) ($trigger['max_width'] ?? 560)));
            $showOnce = ! isset($trigger['show_once_per_visitor']) || ! empty($trigger['show_once_per_visitor']);
            $popupKey = 'fpb_popup_' . $popup->id;
            $renderedPopup = $renderer->renderSchema($popup->getPublishedSchema(), ['page' => $page ?? null, 'popup' => $popup]);
        @endphp
        <div
            x-data="{
                open: false,
                shown: false,
                storageKey: @js($popupKey),
                storageGet(key) {
                    try { return localStorage.getItem(key); } catch (e) { return null; }
                },
                storageSet(key) {
                    try { localStorage.setItem(key, '1'); } catch (e) { /* storage unavailable */ }
                },
                init() {
                    if (@js($showOnce) && this.storageGet(this.storageKey)) {
                        this.shown = true;
                        return;
                    }
                    const type = @js($triggerType);
                    if (type === 'on_load') {
                        setTimeout(() => this.show(), @js($delaySeconds) * 1000);
                    } else if (type === 'on_scroll') {
                        const percent = @js($scrollPercent);
                        const handler = () => {
                            const scrolled = (window.scrollY + window.innerHeight) / document.body.scrollHeight * 100;
                            if (scrolled >= percent) {
                                this.show();
                                window.removeEventListener('scroll', handler);
                            }
                        };
                        window.addEventListener('scroll', handler, { passive: true });
                    } else if (type === 'exit_intent') {
                        document.addEventListener('mouseout', (e) => {
                            if (! e.relatedTarget && e.clientY <= 0) {
                                this.show();
                            }
                        });
                    }
                },
                show() {
                    if (this.shown) return;
                    this.shown = true;
                    this.open = true;
                    if (@js($showOnce)) this.storageSet(this.storageKey);
                    this.$nextTick(() => {
                        const btn = this.$el.querySelector('.fpb-popup-close');
                        if (btn) btn.focus();
                    });
                },
                close() { this.open = false; }
            }"
            x-show="open"
            x-cloak
            x-on:keydown.escape.window="close()"
            x-on:click.self="close()"
            role="dialog"
            aria-modal="true"
            aria-labelledby="{{ $popupKey }}_title"
            style="position: fixed; inset: 0; z-index: 9998; display: flex; align-items: center; justify-content: center; background: rgba(15, 23, 42, 0.6); padding: 24px;"
            id="{{ $popupKey }}"
            data-fpb-popup="{{ $popup->id }}"
        >
            <div style="position: relative; background: #ffffff; border-radius: 12px; max-width: {{ $maxWidth }}px; width: 100%; max-height: 90vh; overflow-y: auto; padding: 24px; box-shadow: 0 25px 60px rgba(0,0,0,0.3);">
                <h2 id="{{ $popupKey }}_title" style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0,0,0,0);">{{ $popup->title }}</h2>
                <button
                    type="button"
                    x-on:click="close()"
                    class="fpb-popup-close"
                    aria-label="Close popup"
                    style="position: absolute; top: 12px; right: 12px; background: #f1f5f9; border: 0; border-radius: 50%; width: 30px; height: 30px; cursor: pointer; font-size: 16px; line-height: 1;"
                >×</button>
                {!! $renderedPopup->toHtml() !!}
            </div>
        </div>
    @endforeach
@endif
