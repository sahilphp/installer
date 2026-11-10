@extends('layouts.auth')

@section('title')Forgot password · {{ app_name ?? 'SahilPHP' }}@endsection

@section('content')
    <div class="space-y-6">
        <div class="space-y-1.5 text-center">
            <h1 class="text-xl font-semibold tracking-tight">Forgot your password?</h1>
            <p class="text-sm text-slate-500">
                Enter the email for your account and we will send a reset link.
            </p>
        </div>

        @if (status ?? null)
            <div class="flex items-start gap-2 rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2.5 text-sm text-emerald-800" role="status">
                <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-7.5 7.5a1 1 0 0 1-1.4 0L3.3 9.7a1 1 0 1 1 1.4-1.4l3.8 3.8 6.8-6.8a1 1 0 0 1 1.4 0Z" clip-rule="evenodd" />
                </svg>
                <span>{{ status }}</span>
            </div>
        @endif

        <form class="space-y-4" method="post" action="/forgot-password" novalidate>
            <input type="hidden" name="_token" value="{{ csrf_token }}">

            <div class="space-y-2">
                <label class="text-sm font-medium leading-none" for="email">Email</label>
                <input class="flex h-10 w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm transition placeholder:text-slate-400 focus-visible:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/30@if (errors.email ?? null) border-red-400 focus-visible:border-red-400 focus-visible:ring-red-200@endif"
                    id="email" name="email" type="email" value="{{ old.email ?? '' }}" placeholder="you@example.com"
                    autocomplete="email" required>
                @if (errors.email ?? null)
                    <p class="text-xs font-medium text-red-600" id="email-error">{{ errors.email }}</p>
                @endif
            </div>

            <button class="inline-flex h-10 w-full items-center justify-center rounded-md bg-primary px-4 text-sm font-semibold text-primary-foreground shadow-sm transition hover:opacity-90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40"
                type="submit">Send reset link</button>
        </form>

        <p class="text-center text-sm text-slate-500">
            <a class="font-medium text-primary hover:underline" href="/login">← Back to sign in</a>
        </p>
    </div>
@endsection
