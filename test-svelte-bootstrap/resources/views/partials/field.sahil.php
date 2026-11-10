{{--
    Reusable labelled input with validation error.

    Layouts differ in class names, not structure, so pages pass the classes
    that match their framework. `error` is rendered only when it is present.
--}}
<div class="{{ wrapper_class ?? 'form-field' }}">
    <label class="{{ label_class ?? 'form-label' }}" for="{{ id }}">{{ label }}</label>

    <input
        class="{{ input_class ?? 'input' }} @if (error ?? null) is-invalid @endif"
        id="{{ id }}"
        name="{{ name }}"
        type="{{ type ?? 'text' }}"
        value="{{ value ?? '' }}"
        @if (required ?? false) required @endif
        @if (placeholder ?? null) placeholder="{{ placeholder }}" @endif
        @if (autocomplete ?? null) autocomplete="{{ autocomplete }}" @endif
        @if (error ?? null) aria-invalid="true" aria-describedby="{{ id }}-error" @endif
    >

    @if (error ?? null)
        <p class="{{ error_class ?? 'error' }}" id="{{ id }}-error">{{ error }}</p>
    @endif
</div>
