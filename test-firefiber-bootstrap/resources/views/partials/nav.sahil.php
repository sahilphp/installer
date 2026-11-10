{{-- Shared navigation component for the Bootstrap profile. --}}
<header class="border-bottom bg-white sticky-top">
    <nav class="navbar navbar-expand-lg navbar-light container py-2" aria-label="Main navigation">
        @include('partials.brand', ['home_url' => home_url ?? '/'])

        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse"
            data-bs-target="#primary-navigation" aria-controls="primary-navigation"
            aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="primary-navigation">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
                @foreach (links as link)
                    <li class="nav-item">
                        <a class="nav-link px-3" href="{{ link.href }}"
                            @if (link.current ?? false) aria-current="page" @endif>{{ link.label }}</a>
                    </li>
                @endforeach

                @if (cta ?? null)
                    <li class="nav-item ms-lg-2">
                        <a class="btn btn-primary btn-sm px-3" href="{{ cta.href }}">{{ cta.label }}</a>
                    </li>
                @endif
            </ul>
        </div>
    </nav>
</header>
