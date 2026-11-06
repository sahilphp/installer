@extends('layouts.auth')

@section('title')Reset password · {{ app_name ?? 'SahilPHP' }}@endsection

@section('content')
    <div class="text-center mb-4">
        <h1 class="h4 fw-semibold mb-1">Reset your password</h1>
        <p class="text-muted small mb-0">Enter your new password below.</p>
    </div>

    @if (status ?? null)
        <div class="alert alert-success py-2 small" role="status">{{ status }}</div>
    @endif

    <form method="post" action="/reset-password" novalidate>
        <input type="hidden" name="_token" value="{{ csrf_token }}">

        <div class="mb-3">
            <label class="form-label small fw-medium" for="token">Reset token</label>
            <input class="form-control" id="token" name="token" type="hidden" value="{{ token }}">
        </div>

        <div class="mb-3">
            <label class="form-label small fw-medium" for="email">Email</label>
            <input class="form-control@if (errors.email ?? null) is-invalid@endif" id="email" name="email" type="email"
                value="{{ old.email ?? '' }}" placeholder="you@example.com" autocomplete="email" required>
            @if (errors.email ?? null)
                <div class="invalid-feedback" id="email-error">{{ errors.email }}</div>
            @endif
        </div>

        <div class="mb-3">
            <label class="form-label small fw-medium" for="password">New password</label>
            <input class="form-control@if (errors.password ?? null) is-invalid@endif" id="password" name="password" type="password"
                placeholder="At least 8 characters" autocomplete="new-password" minlength="8" required>
            @if (errors.password ?? null)
                <div class="invalid-feedback" id="password-error">{{ errors.password }}</div>
            @else
                <p class="text-xs text-muted small">Use at least 8 characters, including a number.</p>
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

        <button class="btn btn-primary w-100" type="submit">Reset password</button>
    </form>
@endsection
