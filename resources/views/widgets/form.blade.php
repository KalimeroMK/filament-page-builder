@php
    $fields = is_array($settings['fields'] ?? null) ? $settings['fields'] : [];
    $honeypot = ! isset($settings['honeypot']) || ! empty($settings['honeypot']);
    $submitLabel = (string) ($settings['submit_label'] ?? 'Send Message');
    $actionUrl = (string) ($settings['form_action_url'] ?? '#');
    $successMessage = session('fpb_form_success');
    $successElement = session('fpb_form_element');
@endphp
<div
    @if($htmlId !== '') id="{{ $htmlId }}" @endif
    class="{{ $classes }} fpb-form-wrap"
    data-fpb-id="{{ $id }}"
    data-fpb-type="form"
>
    @if($successMessage && $successElement === $id)
        <div class="fpb-alert fpb-alert--success" style="margin-bottom: 12px;">
            {{ $successMessage }}
        </div>
    @endif

    <form method="POST" action="{{ $actionUrl }}" class="fpb-form" style="display: flex; flex-direction: column; gap: var(--fpb-gap, 12px);">
        @csrf
        @if($honeypot)
            <input type="text" name="_hp" value="" tabindex="-1" autocomplete="off" style="position: absolute; left: -9999px; opacity: 0;" aria-hidden="true" />
        @endif

        @foreach($fields as $field)
            @php
                if (! is_array($field)) { continue; }
                $name = (string) ($field['name'] ?? '');
                if ($name === '') { continue; }
                $type = (string) ($field['type'] ?? 'text');
                $label = (string) ($field['label'] ?? $name);
                $required = ! empty($field['required']);
            @endphp
            <div class="fpb-form-field">
                <label for="fpb_{{ $id }}_{{ $name }}" style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px;">
                    {{ $label }}@if($required) <span style="color: #dc2626;">*</span>@endif
                </label>
                @if($type === 'textarea')
                    <textarea id="fpb_{{ $id }}_{{ $name }}" name="{{ $name }}" rows="4" @if($required) required @endif style="width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px;">{{ old($name) }}</textarea>
                @elseif($type === 'select')
                    @php $options = array_filter(array_map('trim', explode(',', (string) ($field['options'] ?? '')))); @endphp
                    <select id="fpb_{{ $id }}_{{ $name }}" name="{{ $name }}" @if($required) required @endif style="width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px;">
                        <option value="">-- Select --</option>
                        @foreach($options as $opt)
                            <option value="{{ $opt }}" @selected(old($name) === $opt)>{{ $opt }}</option>
                        @endforeach
                    </select>
                @elseif($type === 'checkbox')
                    <input type="hidden" name="{{ $name }}" value="0" />
                    <input type="checkbox" id="fpb_{{ $id }}_{{ $name }}" name="{{ $name }}" value="1" @checked(old($name)) style="width: 18px; height: 18px;" />
                @else
                    <input type="{{ $type === 'number' ? 'number' : $type }}" id="fpb_{{ $id }}_{{ $name }}" name="{{ $name }}" value="{{ old($name) }}" @if($required) required @endif style="width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px;" />
                @endif
                @error($name)
                    <div style="color: #dc2626; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                @enderror
            </div>
        @endforeach

        <button type="submit" class="fpb-button" style="align-self: flex-start;">
            {{ $submitLabel }}
        </button>
    </form>
</div>
