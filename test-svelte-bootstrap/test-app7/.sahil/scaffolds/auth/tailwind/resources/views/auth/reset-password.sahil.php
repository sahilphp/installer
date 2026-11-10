@extends('layouts.auth')

@section('title')Reset password · {{ app_name ?? 'SahilPHP' }}@endsection

@section('content')
    <div class="space-y-6">
        <div class="space-y-1.5 text-center">
            <h1 class="text-xl font-semibold tracking-tight">Choose a new password</h1>
            <p class="text-sm text-slate-500">
                Your reset link is single use. After saving, sign in with the new password.
            </p>
        </div>

        @if (status ?? null)
            <div class="rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2.5 text-sm text-emerald-800" role="status">
                {{ status }}
            </div>
        @endif

        <form class="space-y-4" method="post" action="/reset-password" novalidate>
            <input type="hidden" name="_token" value="{{ csrf_token }}">
            <input type="hidden" name="token" value="{{ token ?? '' }}">

            @if (errors.token ?? null)
                <p class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700" role="alert">{{ errors.token }}</p>
            @endif

            <div class="space-y-2">
                <label class="text-sm font-medium leading-none" for="email">Email</label>
                <input class="flex h-10 w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm transition placeholder:text-slate-400 focus-visible:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/30@if (errors.email ?? null) border-red-400 focus-visible:border-red-400 focus-visible:ring-red-200@endif"
                    id="email" name="email" type="email" value="{{ old.email ?? '' }}" placeholder="you@example.com"
                    autocomplete="email" required>
                @if (errors.email ?? null)
                    <p class="text-xs font-medium text-red-600" id="email-error">{{ errors.email }}</p>
                @endif
            </div>

            <div class="space-y-2">
                <label class="text-sm font-medium leading-none" for="password">New password</label>
                <input class="flex h-10 w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm transition placeholder:text-slate-400 focus-visible:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/30@if (errors.password ?? null) border-red-400 focus-visible:border-red-400 focus-visible:ring-red-200@endif"
                    id="password" name="password" type="password" placeholder="At least 8 characters"
                    autocomplete="new-password" minlength="8" required>
                @if (errors.password ?? null)
                    <p class="text-xs font-medium text-red-600" id="password-error">{{ errors.password }}</p>
                @endif
            </div>

            <div class="space-y-2">
                <label class="text-sm font-medium leading-none" for="password_confirmation">Confirm new password</label>
                <input class="flex h-10 w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm transition placeholder:text-slate-400 focus-visible:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/30@if (errors.password_confirmation ?? null) border-red-400 focus-visible:border-red-400 focus-visible:ring-red-200@endif"
                    id="password_confirmation" name="password_confirmation" type="password"
                    placeholder="Repeat your password" autocomplete="new-password" required>
                @if (errors.password_confirmation ?? null)
                    <p class="text-xs font-medium text-red-600" id="password_confirmation-error">{{ errors.password_confirmation }}</p>
                @endif
            </div>

            <button class="inline-flex h-10 w-full items-center justify-center rounded-md bg-primary px-4 text-sm font-semibold text-primary-foreground shadow-sm transition hover:opacity-90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40"
                type="submit">Reset password</button>
        </form>

        <p class="text-center text-sm text-slate-500">
            <a class="font-medium text-primary hover:underline" href="/login">← Back to sign in</a>
        </p>
    </div>
@endsection
