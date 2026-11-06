@php
    $layout = in_array(($settings['layout'] ?? 'grid'), ['grid', 'list', 'carousel'], true) ? $settings['layout'] : 'grid';
    $renderedItems = $settings['rendered_loop_items'] ?? [];
    $loopCss = (string) ($settings['loop_items_css'] ?? '');
    $configError = $settings['loop_config_error'] ?? null;
    $hasMore = ! empty($settings['loop_has_more']);
    $pagination = ($settings['pagination'] ?? 'none') === 'load_more' ? 'load_more' : 'none';
    $pageModel = $context['page'] ?? null;
    $loadMoreUrl = $pageModel !== null
        ? route('filament-page-builder.loop.load', ['page' => $pageModel->getKey(), 'elementId' => $id])
        : '#';
@endphp
<div
    @if($htmlId !== '') id="{{ $htmlId }}" @endif
    class="{{ $classes }} fpb-loop fpb-loop--{{ $layout }}"
    data-fpb-id="{{ $id }}"
    data-fpb-type="loop"
    @if($pagination === 'load_more')
        x-data="{ page: 1, hasMore: @js($hasMore), loading: false, loadError: false,
            async loadMore() {
                if (this.loading || ! this.hasMore) return;
                this.loading = true;
                this.loadError = false;
                try {
                    const response = await fetch(@js($loadMoreUrl) + '?page=' + (this.page + 1));
                    if (! response.ok) {
                        this.loadError = true;
                        return;
                    }
                    const data = await response.json();
                    if (typeof data.html !== 'string' || typeof data.page !== 'number') {
                        this.loadError = true;
                        return;
                    }
                    this.$refs.items.insertAdjacentHTML('beforeend', data.html);
                    if (data.css) {
                        const style = document.createElement('style');
                        style.textContent = data.css;
                        document.head.appendChild(style);
                    }
                    this.page = data.page;
                    this.hasMore = !! data.has_more;
                } catch (e) {
                    this.loadError = true;
                } finally {
                    this.loading = false;
                }
            }
        }"
    @endif
>
    @if($configError !== null)
        <div style="padding: 12px; border: 1px solid #fecaca; background: #fef2f2; color: #991b1b; border-radius: 6px; font-size: 13px;">
            {{ $configError }}
        </div>
    @endif

    @if($loopCss !== '')
        <style class="fpb-loop-item-styles">{!! $loopCss !!}</style>
    @endif

    <div
        class="fpb-loop-items"
        x-ref="items"
    >
        @foreach($renderedItems as $item)
            <div class="fpb-loop-item">
                {!! $item->toHtml() !!}
            </div>
        @endforeach
        @if($renderedItems === [] && ! empty($context['is_editor']) && $configError === null)
            <div style="padding: 16px; border: 1px dashed #cbd5e1; border-radius: 6px; text-align: center; color: #94a3b8; font-size: 12px;">
                No records found or model class not allowed. Configure the Loop Builder settings and allow the model in config.
            </div>
        @endif
    </div>

    @if($pagination === 'load_more')
        <div style="text-align: center; margin-top: 16px;">
            <div x-show="hasMore">
                <button
                    type="button"
                    class="fpb-button fpb-button--secondary"
                    x-on:click="loadMore()"
                    x-bind:disabled="loading"
                    x-text="loading ? 'Loading…' : 'Load More'"
                >Load More</button>
            </div>
            <div x-show="loadError" x-cloak style="color: #dc2626; font-size: 13px; margin-top: 8px;">
                Could not load more items. Please try again.
            </div>
        </div>
    @endif

    @if(trim($childrenHtml) !== '')
        <div class="fpb-loop-children">
            {!! $childrenHtml !!}
        </div>
    @endif
</div>
