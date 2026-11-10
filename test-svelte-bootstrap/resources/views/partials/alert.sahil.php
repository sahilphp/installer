{{--
    Reusable alert. Pages pass the framework's classes, for example:
    @include('partials.alert', ['class' => 'alert alert-success', ...])
--}}
<div class="{{ class ?? 'alert' }}" role="alert">
    @if (title ?? null)
        <p class="{{ title_class ?? '' }}"><strong>{{ title }}</strong></p>
    @endif

    <p class="{{ message_class ?? '' }}">{{ message }}</p>
</div>
