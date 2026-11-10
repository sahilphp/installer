@extends('layouts.app')

@section('title')UI kit · {{ app_name ?? 'SahilPHP' }}@endsection

@section('head')
    <link rel="stylesheet" href="{{ asset('css/ui-kit.css') }}">
@endsection

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="mb-4">
                <h1 class="h3 mb-2">{{ heading }}</h1>
                <p class="text-muted mb-2">{{ intro }}</p>
                <span class="badge bg-primary">{{ profile_label }}</span>
            </div>

            <div class="mb-4">
                @foreach (alerts as alert)
                    @if (alert.type == 'success')
                        @include('partials.alert', [
                            'class' => 'alert alert-success',
                            'title_class' => 'alert-heading h6 mb-1',
                            'message_class' => 'mb-0',
                            'title' => alert.title,
                            'message' => alert.message,
                        ])
                    @endif

                    @if (alert.type == 'warning')
                        @include('partials.alert', [
                            'class' => 'alert alert-warning',
                            'title_class' => 'alert-heading h6 mb-1',
                            'message_class' => 'mb-0',
                            'title' => alert.title,
                            'message' => alert.message,
                        ])
                    @endif

                    @if (alert.type == 'danger')
                        @include('partials.alert', [
                            'class' => 'alert alert-danger',
                            'title_class' => 'alert-heading h6 mb-1',
                            'message_class' => 'mb-0',
                            'title' => alert.title,
                            'message' => alert.message,
                        ])
                    @endif
                @endforeach
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-3">Form</h2>

                    <form method="post" action="/ui-kit">
                        <div class="row">
                            <div class="col-md-6">
                                @include('partials.field', [
                                    'wrapper_class' => 'mb-3',
                                    'label_class' => 'form-label',
                                    'input_class' => 'form-control',
                                    'error_class' => 'invalid-feedback d-block',
                                    'id' => 'name',
                                    'label' => 'Name',
                                    'name' => 'name',
                                    'value' => form.name,
                                    'error' => errors.name ?? null,
                                    'required' => true,
                                ])
                            </div>

                            <div class="col-md-6">
                                @include('partials.field', [
                                    'wrapper_class' => 'mb-3',
                                    'label_class' => 'form-label',
                                    'input_class' => 'form-control',
                                    'error_class' => 'invalid-feedback d-block',
                                    'id' => 'email',
                                    'label' => 'Email',
                                    'name' => 'email',
                                    'type' => 'email',
                                    'value' => form.email,
                                    'error' => errors.email ?? null,
                                    'required' => true,
                                ])
                            </div>
                        </div>

                        @include('partials.select', [
                            'wrapper_class' => 'mb-3',
                            'label_class' => 'form-label',
                            'select_class' => 'form-select',
                            'error_class' => 'invalid-feedback d-block',
                            'id' => 'role',
                            'label' => 'Role',
                            'name' => 'role',
                            'options' => roles,
                            'selected' => form.role,
                        ])

                        @include('partials.field', [
                            'wrapper_class' => 'mb-3',
                            'label_class' => 'form-label',
                            'input_class' => 'form-control',
                            'id' => 'notes',
                            'label' => 'Notes',
                            'name' => 'notes',
                            'value' => form.notes,
                        ])

                        <button class="btn btn-primary" type="submit">Save changes</button>
                    </form>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-3">Team</h2>

                    @include('partials.table', [
                        'wrapper_class' => 'table-responsive',
                        'table_class' => 'table table-striped align-middle mb-0',
                        'headers' => ['Name', 'Email', 'Role', 'Status'],
                        'rows' => users,
                    ])
                </div>
            </div>

            <p class="demo-note" data-ui-kit-js>Page-specific JavaScript did not run.</p>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('js/ui-kit.js') }}" defer></script>
@endsection
