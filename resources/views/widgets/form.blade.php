@php
    $fields = is_array($settings['fields'] ?? null) ? array_values(array_filter($settings['fields'], 'is_array')) : [];
    $honeypot = ! isset($settings['honeypot']) || ! empty($settings['honeypot']);
    $turnstileSiteKey = (string) config('filament-page-builder.forms.turnstile.site_key', '');
    $turnstileSecret = (string) config('filament-page-builder.forms.turnstile.secret_key', '');
    $turnstileEnabled = ! empty($settings['turnstile']) && $turnstileSiteKey !== '' && $turnstileSecret !== '';
    $submitLabel = (string) ($settings['submit_label'] ?? 'Send Message');
    $actionUrl = (string) ($settings['form_action_url'] ?? '#');
    $successMessage = session('fpb_form_success');
    $successElement = session('fpb_form_element');
    $maxStep = max(1, ...array_map(fn (array $f): int => max(1, (int) ($f['step'] ?? 1)), $fields ?: [['step' => 1]]));
    $errorBag = 'fpb_' . $id;
@endphp
<div
    @if($htmlId !== '') id="{{ $htmlId }}" @endif
    class="{{ $classes }} fpb-form-wrap"
    data-fpb-id="{{ $id }}"
    data-fpb-type="form"
    x-data="{
        currentStep: 1,
        maxStep: {{ $maxStep }},
        values: {},
        normalize(val) {
            if (val === true) return '1';
            if (val === false || val === undefined || val === null) return '0';
            return String(val);
        },
        isVisible(field, value) {
            if (! field) return true;
            return this.normalize(this.values[field]) === String(value);
        },
        fieldActive(step, field, value) {
            return this.currentStep === step && this.isVisible(field, value);
        }
    }"
>
    @if($successMessage && $successElement === $id)
        <div class="fpb-alert fpb-alert--success" style="margin-bottom: 12px;">
            {{ $successMessage }}
        </div>
    @endif

    @if($maxStep > 1)
        <div style="font-size: 12px; color: #64748b; margin-bottom: 8px;">
            Step <span x-text="currentStep">1</span> of {{ $maxStep }}
        </div>
    @endif

    <form method="POST" action="{{ $actionUrl }}" class="fpb-form" style="display: flex; flex-direction: column; gap: var(--fpb-gap, 12px);">
        @csrf
        @if(!empty($page))
            <input type="hidden" name="_host_page" value="{{ $page->getKey() }}" />
        @endif
        @if($honeypot)
            <input type="text" name="_hp" value="" tabindex="-1" autocomplete="off" style="position: absolute; left: -9999px; opacity: 0;" aria-hidden="true" />
        @endif

        @foreach($fields as $field)
            @php
                $name = (string) ($field['name'] ?? '');
                if ($name === '') { continue; }
                $type = (string) ($field['type'] ?? 'text');
                $label = (string) ($field['label'] ?? $name);
                $required = ! empty($field['required']);
                $step = max(1, (int) ($field['step'] ?? 1));
                $visibleIfField = trim((string) ($field['visible_if_field'] ?? ''));
                $visibleIfValue = (string) ($field['visible_if_value'] ?? '');
                $activeExpr = 'fieldActive(' . $step . ', ' . \Illuminate\Support\Js::from($visibleIfField) . ', ' . \Illuminate\Support\Js::from($visibleIfValue) . ')';
            @endphp
            <div
                class="fpb-form-field"
                x-show="{{ $activeExpr }}"
                @if($step !== 1 || $visibleIfField !== '') x-cloak @endif
            >
                <label for="fpb_{{ $id }}_{{ $name }}" style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px;">
                    {{ $label }}@if($required) <span style="color: #dc2626;">*</span>@endif
                </label>
                @if($type === 'textarea')
                    <textarea id="fpb_{{ $id }}_{{ $name }}" name="{{ $name }}" rows="4" x-model="values['{{ $name }}']" x-bind:disabled="!{{ $activeExpr }}" @if($required) x-bind:required="{{ $activeExpr }}" @endif style="width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px;">{{ old($name, $errorBag) }}</textarea>
                @elseif($type === 'select')
                    @php $options = array_filter(array_map('trim', explode(',', (string) ($field['options'] ?? '')))); @endphp
                    <select id="fpb_{{ $id }}_{{ $name }}" name="{{ $name }}" x-model="values['{{ $name }}']" x-bind:disabled="!{{ $activeExpr }}" @if($required) x-bind:required="{{ $activeExpr }}" @endif style="width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px;">
                        <option value="">-- Select --</option>
                        @foreach($options as $opt)
                            <option value="{{ $opt }}" @selected(old($name, $errorBag) === $opt)>{{ $opt }}</option>
                        @endforeach
                    </select>
                @elseif($type === 'checkbox')
                    <input type="hidden" name="{{ $name }}" value="0" />
                    <input type="checkbox" id="fpb_{{ $id }}_{{ $name }}" name="{{ $name }}" value="1" x-model="values['{{ $name }}']" x-bind:disabled="!{{ $activeExpr }}" @checked(old($name, $errorBag)) style="width: 18px; height: 18px;" />
                @else
                    <input type="{{ $type === 'number' ? 'number' : $type }}" id="fpb_{{ $id }}_{{ $name }}" name="{{ $name }}" value="{{ old($name, $errorBag) }}" x-model="values['{{ $name }}']" x-bind:disabled="!{{ $activeExpr }}" @if($required) x-bind:required="{{ $activeExpr }}" @endif style="width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px;" />
                @endif
                @error($name, $errorBag)
                    <div style="color: #dc2626; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                @enderror
            </div>
        @endforeach

        @if($turnstileEnabled)
            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
            <div class="cf-turnstile" data-sitekey="{{ $turnstileSiteKey }}"></div>
            @error('cf-turnstile-response', $errorBag)
                <div style="color: #dc2626; font-size: 12px;">{{ $message }}</div>
            @enderror
        @endif

        @error('fpb_form', $errorBag)
            <div class="fpb-alert fpb-alert--danger">{{ $message }}</div>
        @enderror

        <div style="display: flex; gap: 8px; align-items: center;">
            @if($maxStep > 1)
                <button type="button" x-show="currentStep > 1" x-cloak x-on:click="currentStep--" class="fpb-button fpb-button--secondary">
                    ← Back
                </button>
                <button type="button" x-show="currentStep < maxStep" x-on:click="currentStep++" class="fpb-button">
                    Next →
                </button>
            @endif
            <button type="submit" class="fpb-button" x-show="currentStep === maxStep">
                {{ $submitLabel }}
            </button>
        </div>
    </form>
</div>
