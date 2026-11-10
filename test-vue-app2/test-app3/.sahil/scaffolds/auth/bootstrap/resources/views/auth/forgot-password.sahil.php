@extends('layouts.auth')

@section('title')Forgot password · {{ app_name ?? 'SahilPHP' }}@endsection

@section('content')
    <div class="text-center mb-4">
        <h1 class="h4 fw-semibold mb-1">Forgot your password?</h1>
        <p class="text-muted small mb-0">Enter the email for your account and we will send a reset link.</p>
    </div>

    @if (status ?? null)
        <div class="alert alert-success d-flex align-items-start gap-2 py-2 small" role="status">
            <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" class="mt-1 flex-shrink-0" aria-hidden="true">
                <path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-7.5 7.5a1 1 0 0 1-1.4 0L3.3 9.7a1 1 0 1 1 1.4-1.4l3.8 3.8 6.8-6.8a1 1 0 0 1 1.4 0Z" clip-rule="evenodd" />
            </svg>
            <span>{{ status }}</span>
        </div>
    @endif

    <form method="post" action="/forgot-password" novalidate>
        <input type="hidden" name="_token" value="{{ csrf_token }}">

        <div class="mb-4">
            <label class="form-label small fw-medium" for="email">Email</label>
            <input class="form-control@if (errors.email ?? null) is-invalid@endif" id="email" name="email" type="email"
                value="{{ old.email ?? '' }}" placeholder="you@example.com" autocomplete="email" required>
            @if (errors.email ?? null)
                <div class="invalid-feedback" id="email-error">{{ errors.email }}</div>
            @endif
        </div>

        <button class="btn btn-primary w-100" type="submit">Send reset link</button>
    </form>

    <p class="text-center small mt-4 mb-0">
        <a class="text-decoration-none fw-medium" href="/login">← Back to sign in</a>
    </p>
@endsection
