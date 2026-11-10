@extends('layouts.app')

@section('title')Welcome · {{ app_name ?? 'SahilPHP' }}@endsection

@section('content')
    <section class="hero-glow position-relative rounded-4 px-3 px-md-4 py-5 text-center">
        <span class="badge rounded-pill border text-bg-light fw-medium mb-4">
            <span class="d-inline-block rounded-circle bg-success me-1" style="width: .45rem; height: .45rem;"></span>
            Application ready
        </span>

        <img class="d-block mx-auto mb-4" src="{{ asset('logo.png') }}" alt="SahilPHP"
            width="380" height="160" style="height: 4rem; width: auto;" decoding="async">

        <h1 class="display-5 fw-bold text-body-emphasis mb-3">
            Welcome to <span class="brand-text">{{ app_name ?? 'SahilPHP' }}</span>
        </h1>

        <p class="lead text-muted mx-auto mb-4" style="max-width: 38rem;">
            Your application runs on the SahilPHP framework with Bootstrap 5.
            Routing, views, sessions, migrations, queues and the scheduler are already wired up.
        </p>

        <div class="d-flex flex-wrap justify-content-center gap-2">
            <a class="btn btn-primary px-4" href="/ui-kit">
                Explore the UI kit
                <svg class="ms-1" width="16" height="16" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.19L10.22 5.53a.75.75 0 1 1 1.06-1.06l5 5a.75.75 0 0 1 0 1.06l-5 5a.75.75 0 1 1-1.06-1.06l3.72-3.72H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd" />
                </svg>
            </a>
            <a class="btn btn-outline-secondary px-4" href="/health">Check health endpoint</a>
        </div>

        <p class="small text-muted mt-4 mb-0">
            PHP {{ php_version ?? '' }} · {{ profile_label ?? 'Bootstrap 5 profile' }}
        </p>
    </section>

    <section class="mx-auto mt-4" style="max-width: 34rem;" aria-label="Useful commands">
        <div class="card border shadow-sm overflow-hidden">
            <div class="card-header bg-body-tertiary d-flex align-items-center gap-2 py-2">
                <span class="rounded-circle bg-secondary-subtle" style="width: .55rem; height: .55rem;"></span>
                <span class="rounded-circle bg-secondary-subtle" style="width: .55rem; height: .55rem;"></span>
                <span class="rounded-circle bg-secondary-subtle" style="width: .55rem; height: .55rem;"></span>
                <span class="ms-2 text-uppercase text-muted fw-semibold" style="font-size: .68rem; letter-spacing: .06em;">Terminal</span>
            </div>

            <ul class="list-group list-group-flush font-monospace small">
                <li class="list-group-item d-flex align-items-center gap-3 py-2">
                    <span class="text-secondary-subtle">$</span>
                    <span>php sahil start</span>
                    <span class="ms-auto font-sans text-muted" style="font-size: .75rem;">dev server</span>
                </li>
                <li class="list-group-item d-flex align-items-center gap-3 py-2">
                    <span class="text-secondary-subtle">$</span>
                    <span>php sahil unda:auth</span>
                    <span class="ms-auto font-sans text-muted" style="font-size: .75rem;">auth pages</span>
                </li>
                <li class="list-group-item d-flex align-items-center gap-3 py-2">
                    <span class="text-secondary-subtle">$</span>
                    <span>php sahil ui:dev</span>
                    <span class="ms-auto font-sans text-muted" style="font-size: .75rem;">vite</span>
                </li>
            </ul>
        </div>
    </section>

    <section class="mt-5">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-end gap-2 mb-3">
            <div>
                <h2 class="h4 fw-semibold mb-1">Everything is already connected</h2>
                <p class="text-muted small mb-0">
                    Start in <code>routes/web.php</code>, build views in <code>resources/views</code>.
                </p>
            </div>
            <a class="small fw-medium text-decoration-none" href="/ui-kit">See the components →</a>
        </div>

        <div class="row g-3">
            <div class="col-md-6 col-lg-4">
                <article class="card h-100 border shadow-sm">
                    <div class="card-body p-4">
                        <span class="feature-icon mb-3">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M4 7h10a4 4 0 0 1 0 8H8" /><path d="M11 18l-3 3 3 3" />
                            </svg>
                        </span>
                        <h3 class="h6 fw-semibold">Routing &amp; HTTP</h3>
                        <p class="text-muted small mb-0">
                            Named routes, middleware, CSRF protection and JSON responses are registered at boot.
                        </p>
                    </div>
                </article>
            </div>

            <div class="col-md-6 col-lg-4">
                <article class="card h-100 border shadow-sm">
                    <div class="card-body p-4">
                        <span class="feature-icon mb-3">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="3" y="4" width="18" height="13" rx="2" /><path d="M8 21h8M12 17v4" />
                            </svg>
                        </span>
                        <h3 class="h6 fw-semibold">Templates &amp; views</h3>
                        <p class="text-muted small mb-0">
                            Layouts, sections and partials with escaping by default and compiled views cached on disk.
                        </p>
                    </div>
                </article>
            </div>

            <div class="col-md-6 col-lg-4">
                <article class="card h-100 border shadow-sm">
                    <div class="card-body p-4">
                        <span class="feature-icon mb-3">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 2" />
                            </svg>
                        </span>
                        <h3 class="h6 fw-semibold">Queues &amp; scheduler</h3>
                        <p class="text-muted small mb-0">
                            Background jobs, retries and scheduled tasks run through one CLI entry point.
                        </p>
                    </div>
                </article>
            </div>
        </div>
    </section>
@endsection
