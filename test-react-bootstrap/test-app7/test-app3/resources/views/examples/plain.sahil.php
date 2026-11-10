@extends('layouts.app')

@section('title')UI kit · {{ app_name ?? 'SahilPHP' }}@endsection

@section('head')
    <link rel="stylesheet" href="{{ asset('css/ui-kit.css') }}">
@endsection

@section('content')
    <section class="ui-kit">
        <header class="ui-kit__header">
            <h1>{{ heading }}</h1>
            <p class="muted">{{ intro }}</p>
            <p><strong>Active profile:</strong> {{ profile_label }}</p>
        </header>

        <section class="ui-kit__alerts" aria-labelledby="ui-kit-alerts">
            <h2 id="ui-kit-alerts">Alerts</h2>

            @foreach (alerts as alert)
                @if (alert.type == 'success')
                    @include('partials.alert', [
                        'class' => 'alert alert-success',
                        'title_class' => 'alert__title',
                        'message_class' => 'alert__message',
                        'title' => alert.title,
                        'message' => alert.message,
                    ])
                @endif

                @if (alert.type == 'warning')
                    @include('partials.alert', [
                        'class' => 'alert alert-warning',
                        'title_class' => 'alert__title',
                        'message_class' => 'alert__message',
                        'title' => alert.title,
                        'message' => alert.message,
                    ])
                @endif

                @if (alert.type == 'danger')
                    @include('partials.alert', [
                        'class' => 'alert alert-danger',
                        'title_class' => 'alert__title',
                        'message_class' => 'alert__message',
                        'title' => alert.title,
                        'message' => alert.message,
                    ])
                @endif
            @endforeach
        </section>

        <section class="ui-kit__form" aria-labelledby="ui-kit-form">
            <h2 id="ui-kit-form">Form</h2>

            <form method="post" action="/ui-kit">
                @include('partials.field', [
                    'id' => 'name',
                    'label' => 'Name',
                    'name' => 'name',
                    'value' => form.name,
                    'error' => errors.name ?? null,
                    'required' => true,
                ])

                @include('partials.field', [
                    'id' => 'email',
                    'label' => 'Email',
                    'name' => 'email',
                    'type' => 'email',
                    'value' => form.email,
                    'error' => errors.email ?? null,
                    'required' => true,
                ])

                @include('partials.select', [
                    'id' => 'role',
                    'label' => 'Role',
                    'name' => 'role',
                    'options' => roles,
                    'selected' => form.role,
                ])

                @include('partials.field', [
                    'id' => 'notes',
                    'label' => 'Notes',
                    'name' => 'notes',
                    'value' => form.notes,
                ])

                <button class="button" type="submit">Save changes</button>
            </form>
        </section>

        <section class="ui-kit__table" aria-labelledby="ui-kit-table">
            <h2 id="ui-kit-table">Team</h2>

            @include('partials.table', [
                'headers' => ['Name', 'Email', 'Role', 'Status'],
                'rows' => users,
            ])
        </section>

        <p class="demo-note" data-ui-kit-js>Page-specific JavaScript did not run.</p>
    </section>
@endsection

@section('scripts')
    <script src="{{ asset('js/ui-kit.js') }}" defer></script>
@endsection
