@php
    $layout = in_array(($settings['layout'] ?? 'grid'), ['grid', 'list', 'carousel'], true) ? $settings['layout'] : 'grid';
    $renderedItems = $settings['rendered_loop_items'] ?? [];
    $loopCss = (string) ($settings['loop_items_css'] ?? '');
    $configError = $settings['loop_config_error'] ?? null;
    $hasMore = ! empty($settings['loop_has_more']);
    $pagination = in_array(($settings['pagination'] ?? 'none'), ['load_more', 'page_numbers'], true) ? $settings['pagination'] : 'none';
    $pageModel = $context['page'] ?? null;
    $loadMoreUrl = $pageModel !== null
        ? route('filament-page-builder.loop.load', ['page' => $pageModel->getKey(), 'elementId' => $id])
        : '#';
    $ajaxFilters = is_array($settings['ajax_filters'] ?? null) ? array_filter($settings['ajax_filters']) : [];
    if ($ajaxFilters === [] && ! empty($settings['ajax_filters_csv'])) {
        $ajaxFilters = array_filter(array_map('trim', explode(',', (string) $settings['ajax_filters_csv'])));
    }
    $totalPages = max(1, (int) ($settings['loop_total_pages'] ?? 1));
@endphp
<div
    @if($htmlId !== '') id="{{ $htmlId }}" @endif
    class="{{ $classes }} fpb-loop fpb-loop--{{ $layout }}"
    data-fpb-id="{{ $id }}"
    data-fpb-type="loop"
    @if($pagination !== 'none')
        x-data="{
            page: 1,
            totalPages: {{ $totalPages }},
            hasMore: @js($hasMore),
            loading: false,
            loadError: false,
            filterField: '',
            filterValue: '',
            async loadPage(targetPage) {
                if (this.loading) return;
                this.loading = true;
                this.loadError = false;
                try {
                    let url = @js($loadMoreUrl) + '?page=' + targetPage;
                    if (this.filterField && this.filterValue !== '') {
                        url += '&filter_field=' + encodeURIComponent(this.filterField) + '&filter_value=' + encodeURIComponent(this.filterValue);
                    }
                    const response = await fetch(url);
                    if (! response.ok) {
                        this.loadError = true;
                        return;
                    }
                    const data = await response.json();
                    if (typeof data.html !== 'string' || typeof data.page !== 'number') {
                        this.loadError = true;
                        return;
                    }
                    this.$refs.items.innerHTML = data.html;
                    if (data.css) {
                        let styleTag = this.$el.querySelector('style.fpb-loop-ajax-styles');
                        if (! styleTag) {
                            styleTag = document.createElement('style');
                            styleTag.className = 'fpb-loop-ajax-styles';
                            this.$el.appendChild(styleTag);
                        }
                        styleTag.textContent = data.css;
                    }
                    this.page = data.page;
                    this.hasMore = !! data.has_more;
                    this.totalPages = data.total_pages || 1;
                } catch (e) {
                    this.loadError = true;
                } finally {
                    this.loading = false;
                }
            },
            async loadMore() {
                if (this.loading || ! this.hasMore) return;
                this.loading = true;
                this.loadError = false;
                try {
                    let url = @js($loadMoreUrl) + '?page=' + (this.page + 1);
                    if (this.filterField && this.filterValue !== '') {
                        url += '&filter_field=' + encodeURIComponent(this.filterField) + '&filter_value=' + encodeURIComponent(this.filterValue);
                    }
                    const response = await fetch(url);
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
                        let styleTag = this.$el.querySelector('style.fpb-loop-ajax-styles');
                        if (! styleTag) {
                            styleTag = document.createElement('style');
                            styleTag.className = 'fpb-loop-ajax-styles';
                            this.$el.appendChild(styleTag);
                        }
                        styleTag.textContent = data.css;
                    }
                    this.page = data.page;
                    this.hasMore = !! data.has_more;
                    this.totalPages = data.total_pages || 1;
                } catch (e) {
                    this.loadError = true;
                } finally {
                    this.loading = false;
                }
            },
            async applyFilter() {
                await this.loadPage(1);
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

    @if($pagination !== 'none' && $ajaxFilters !== [])
        <div class="fpb-loop-filters" style="display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin-bottom: 16px;">
            <select x-model="filterField" style="padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;">
                <option value="">Filter by…</option>
                @foreach($ajaxFilters as $filterName)
                    <option value="{{ $filterName }}">{{ $filterName }}</option>
                @endforeach
            </select>
            <input
                type="text"
                x-model="filterValue"
                placeholder="Value…"
                x-on:keydown.enter="applyFilter()"
                style="padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; width: 160px;"
            />
            <button type="button" class="fpb-button" x-on:click="applyFilter()" style="padding: 6px 14px; font-size: 13px;">
                Apply Filter
            </button>
            <button type="button" class="fpb-button fpb-button--secondary" x-on:click="filterField = ''; filterValue = ''; applyFilter()" style="padding: 6px 14px; font-size: 13px;">
                Clear
            </button>
        </div>
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

    @if($pagination === 'page_numbers')
        <div style="display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 16px; flex-wrap: wrap;">
            <button
                type="button"
                class="fpb-button fpb-button--secondary"
                x-bind:disabled="page <= 1 || loading"
                x-on:click="loadPage(page - 1)"
                style="padding: 6px 12px;"
            >← Prev</button>
            <span style="font-size: 13px; color: #475569;">
                Page <strong x-text="page">1</strong> of <strong x-text="totalPages">1</strong>
            </span>
            <button
                type="button"
                class="fpb-button fpb-button--secondary"
                x-bind:disabled="page >= totalPages || loading"
                x-on:click="loadPage(page + 1)"
                style="padding: 6px 12px;"
            >Next →</button>
            <div x-show="loadError" x-cloak style="color: #dc2626; font-size: 13px; width: 100%; text-align: center; margin-top: 6px;">
                Could not load this page. Please try again.
            </div>
        </div>
    @endif

    @if(trim($childrenHtml) !== '')
        <div class="fpb-loop-children">
            {!! $childrenHtml !!}
        </div>
    @endif
</div>
