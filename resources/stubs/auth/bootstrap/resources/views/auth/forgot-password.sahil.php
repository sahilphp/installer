@extends('layouts.auth')

@section('title')Forgot your password? · {{ app_name ?? 'SahilPHP' }}@endsection

@section('content')
    <div class="text-center mb-4">
        <h1 class="h4 fw-semibold mb-1">Forgot your password?</h1>
        <p class="text-muted small mb-0">No problem. Let us know your email and we will send you a reset link.</p>
    </div>

    @if (status ?? null)
        <div class="alert alert-success py-2 small" role="status">{{ status }}</div>
    @endif

    <form method="post" action="/forgot-password" novalidate>
        <input type="hidden" name="_token" value="{{ csrf_token }}">

        <div class="mb-3">
            <label class="form-label small fw-medium" for="email">Email</label>
            <input class="form-control@if (errors.email ?? null) is-invalid@endif" id="email" name="email" type="email"
                value="{{ old.email ?? '' }}" placeholder="you@example.com" autocomplete="email" required>
            @if (errors.email ?? null)
                <div class="invalid-feedback" id="email-error">{{ errors.email }}</div>
            @endif
        </div>

        <button class="btn btn-primary w-100" type="submit">Send password reset link</button>
    </form>
@endsection
