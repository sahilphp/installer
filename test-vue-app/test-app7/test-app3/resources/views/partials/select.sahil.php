{{--
    Reusable select field. `options` is an associative array of value => label.
--}}
<div class="{{ wrapper_class ?? 'form-field' }}">
    <label class="{{ label_class ?? 'form-label' }}" for="{{ id }}">{{ label }}</label>

    <select
        class="{{ select_class ?? 'select' }} @if (error ?? null) is-invalid @endif"
        id="{{ id }}"
        name="{{ name }}"
        @if (required ?? false) required @endif
        @if (error ?? null) aria-invalid="true" aria-describedby="{{ id }}-error" @endif
    >
        @if (placeholder ?? null)
            <option value="">{{ placeholder }}</option>
        @endif

        @foreach (options as value => label)
            <option value="{{ value }}" @if (value == (selected ?? '')) selected @endif>{{ label }}</option>
        @endforeach
    </select>

    @if (error ?? null)
        <p class="{{ error_class ?? 'error' }}" id="{{ id }}-error">{{ error }}</p>
    @endif
</div>
