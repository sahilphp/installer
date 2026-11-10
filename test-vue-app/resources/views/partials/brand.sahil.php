{{--
    SahilPHP brand lockup for the Bootstrap profile.

    `public/logo.png` is the official lockup (mark + wordmark) and
    `public/logo-mark.png` the mark alone, for tight spaces such as footers.
--}}
<a class="{{ class ?? 'navbar-brand d-inline-flex align-items-center gap-2 m-0 py-0' }}"
    href="{{ home_url ?? '/' }}" aria-label="SahilPHP home">
    @if ((variant ?? 'wordmark') == 'mark')
        <img class="brand__logo brand__logo--mark" src="{{ asset('logo-mark.png') }}" alt="SahilPHP"
            width="109" height="160" decoding="async">
    @else
        <img class="brand__logo" src="{{ asset('logo.png') }}" alt="SahilPHP"
            width="380" height="160" decoding="async">
    @endif
</a>
