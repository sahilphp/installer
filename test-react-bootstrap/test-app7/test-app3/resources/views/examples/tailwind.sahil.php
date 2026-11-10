@extends('layouts.app')

@section('title')UI kit · {{ app_name ?? 'SahilPHP' }}@endsection

@section('head')
    <link rel="stylesheet" href="{{ asset('css/ui-kit.css') }}">
@endsection

@section('content')
    <div class="mx-auto max-w-4xl">
        <header class="mb-8">
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">{{ heading }}</h1>
            <p class="mt-2 text-slate-600">{{ intro }}</p>
            <p class="mt-3 inline-flex items-center rounded-full bg-slate-900 px-3 py-1 text-xs font-semibold text-white">{{ profile_label }}</p>
        </header>

        <section class="mb-8" aria-labelledby="ui-kit-alerts">
            <h2 class="mb-3 text-xl font-semibold text-slate-900" id="ui-kit-alerts">Alerts</h2>

            <div class="space-y-3">
                @foreach (alerts as alert)
                    @if (alert.type == 'success')
                        @include('partials.alert', [
                            'class' => 'rounded-xl border border-green-200 bg-green-50 p-4',
                            'title_class' => 'font-semibold text-green-900',
                            'message_class' => 'mt-1 text-sm text-green-800',
                            'title' => alert.title,
                            'message' => alert.message,
                        ])
                    @endif

                    @if (alert.type == 'warning')
                        @include('partials.alert', [
                            'class' => 'rounded-xl border border-amber-200 bg-amber-50 p-4',
                            'title_class' => 'font-semibold text-amber-900',
                            'message_class' => 'mt-1 text-sm text-amber-800',
                            'title' => alert.title,
                            'message' => alert.message,
                        ])
                    @endif

                    @if (alert.type == 'danger')
                        @include('partials.alert', [
                            'class' => 'rounded-xl border border-red-200 bg-red-50 p-4',
                            'title_class' => 'font-semibold text-red-900',
                            'message_class' => 'mt-1 text-sm text-red-800',
                            'title' => alert.title,
                            'message' => alert.message,
                        ])
                    @endif
                @endforeach
            </div>
        </section>

        <section class="mb-8" aria-labelledby="ui-kit-form">
            <h2 class="mb-3 text-xl font-semibold text-slate-900" id="ui-kit-form">Form</h2>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <form method="post" action="/ui-kit">
                    <div class="grid gap-4 md:grid-cols-2">
                        @include('partials.field', [
                            'wrapper_class' => 'space-y-1',
                            'label_class' => 'block text-sm font-medium text-slate-700',
                            'input_class' => 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm',
                            'error_class' => 'mt-1 text-sm text-red-600',
                            'id' => 'name',
                            'label' => 'Name',
                            'name' => 'name',
                            'value' => form.name,
                            'error' => errors.name ?? null,
                            'required' => true,
                        ])

                        @include('partials.field', [
                            'wrapper_class' => 'space-y-1',
                            'label_class' => 'block text-sm font-medium text-slate-700',
                            'input_class' => 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm',
                            'error_class' => 'mt-1 text-sm text-red-600',
                            'id' => 'email',
                            'label' => 'Email',
                            'name' => 'email',
                            'type' => 'email',
                            'value' => form.email,
                            'error' => errors.email ?? null,
                            'required' => true,
                        ])
                    </div>

                    <div class="mt-4">
                        @include('partials.select', [
                            'wrapper_class' => 'space-y-1',
                            'label_class' => 'block text-sm font-medium text-slate-700',
                            'select_class' => 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm',
                            'error_class' => 'mt-1 text-sm text-red-600',
                            'id' => 'role',
                            'label' => 'Role',
                            'name' => 'role',
                            'options' => roles,
                            'selected' => form.role,
                        ])
                    </div>

                    <div class="mt-4">
                        @include('partials.field', [
                            'wrapper_class' => 'space-y-1',
                            'label_class' => 'block text-sm font-medium text-slate-700',
                            'input_class' => 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm',
                            'id' => 'notes',
                            'label' => 'Notes',
                            'name' => 'notes',
                            'value' => form.notes,
                        ])
                    </div>

                    <button class="mt-6 inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white" type="submit">Save changes</button>
                </form>
            </div>
        </section>

        <section class="mb-8" aria-labelledby="ui-kit-table">
            <h2 class="mb-3 text-xl font-semibold text-slate-900" id="ui-kit-table">Team</h2>

            @include('partials.table', [
                'wrapper_class' => 'overflow-x-auto rounded-2xl border border-slate-200 bg-white',
                'table_class' => 'min-w-full divide-y divide-slate-200 text-left text-sm',
                'headers' => ['Name', 'Email', 'Role', 'Status'],
                'rows' => users,
            ])
        </section>

        <p class="demo-note" data-ui-kit-js>Page-specific JavaScript did not run.</p>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('js/ui-kit.js') }}" defer></script>
@endsection
