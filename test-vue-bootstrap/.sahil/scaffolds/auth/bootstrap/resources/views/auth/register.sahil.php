@extends('layouts.auth')

@section('title')Create account · {{ app_name ?? 'SahilPHP' }}@endsection

@section('content')
    <div class="text-center mb-4">
        <h1 class="h4 fw-semibold mb-1">Create your account</h1>
        <p class="text-muted small mb-0">Start building on SahilPHP in under a minute.</p>
    </div>

    @if (status ?? null)
        <div class="alert alert-success py-2 small" role="status">{{ status }}</div>
    @endif

    <form method="post" action="/register" novalidate>
        <input type="hidden" name="_token" value="{{ csrf_token }}">

        <div class="mb-3">
            <label class="form-label small fw-medium" for="name">Full name</label>
            <input class="form-control@if (errors.name ?? null) is-invalid@endif" id="name" name="name" type="text"
                value="{{ old.name ?? '' }}" placeholder="Ada Lovelace" autocomplete="name" required>
            @if (errors.name ?? null)
                <div class="invalid-feedback" id="name-error">{{ errors.name }}</div>
            @endif
        </div>

        <div class="mb-3">
            <label class="form-label small fw-medium" for="email">Work email</label>
            <input class="form-control@if (errors.email ?? null) is-invalid@endif" id="email" name="email" type="email"
                value="{{ old.email ?? '' }}" placeholder="you@example.com" autocomplete="email" required>
            @if (errors.email ?? null)
                <div class="invalid-feedback" id="email-error">{{ errors.email }}</div>
            @endif
        </div>

        <div class="mb-3">
            <label class="form-label small fw-medium" for="password">Password</label>
            <input class="form-control@if (errors.password ?? null) is-invalid@endif" id="password" name="password" type="password"
                placeholder="At least 8 characters" autocomplete="new-password" minlength="8" required>
            @if (errors.password ?? null)
                <div class="invalid-feedback" id="password-error">{{ errors.password }}</div>
            @else
                <div class="form-text">Use at least 8 characters, including a number.</div>
            @endif
        </div>

        <div class="mb-3">
            <label class="form-label small fw-medium" for="password_confirmation">Confirm password</label>
            <input class="form-control@if (errors.password_confirmation ?? null) is-invalid@endif" id="password_confirmation" name="password_confirmation" type="password"
                placeholder="Repeat your password" autocomplete="new-password" required>
            @if (errors.password_confirmation ?? null)
                <div class="invalid-feedback" id="password_confirmation-error">{{ errors.password_confirmation }}</div>
            @endif
        </div>

        <div class="form-check mb-1">
            <input class="form-check-input@if (errors.terms ?? null) is-invalid@endif" id="terms" name="terms" type="checkbox" value="1"@if (old.terms ?? false) checked@endif>
            <label class="form-check-label small" for="terms">
                I agree to the <a class="text-decoration-none" href="#">terms</a> and
                <a class="text-decoration-none" href="#">privacy policy</a>.
            </label>
            @if (errors.terms ?? null)
                <div class="invalid-feedback" id="terms-error">{{ errors.terms }}</div>
            @endif
        </div>

        <button class="btn btn-primary w-100 mt-3" type="submit">Create account</button>
    </form>

    <p class="text-center text-muted small mt-4 mb-0">
        Already have an account? <a class="text-decoration-none fw-medium" href="/login">Sign in</a>
    </p>
@endsection
