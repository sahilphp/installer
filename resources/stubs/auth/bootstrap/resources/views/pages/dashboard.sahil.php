@extends('layouts.dashboard')

@section('title')Dashboard · {{ app_name ?? 'SahilPHP' }}@endsection

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-4 flex-col flex-md-row gap-3">
        <div>
            <h1 class="h3 fw-semibold">Overview</h1>
            <p class="text-muted small">Welcome back, {{ user.name }}. Here is what happened this week.</p>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            <a class="btn btn-outline-secondary" href="/ui-kit">Components</a>
            <a class="btn btn-primary" href="/register">Invite member</a>

            <form class="d-inline" method="post" action="/logout">
                <input type="hidden" name="_token" value="{{ csrf_token }}">
                <button class="btn btn-outline-secondary" type="submit">Sign out</button>
            </form>
        </div>
    </div>

    <div class="row row-cols-1 row-cols-md-4 g-3">
        @foreach (stats as stat)
            <div class="col">
                <div class="card h-100">
                    <div class="card-body">
                        <h6 class="card-title text-muted">{{ stat.label }}</h6>
                        <p class="card-title display-6">{{ stat.value }}</p>
                        <p class="card-text text-muted small">{{ stat.hint }}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-4 row row-cols-1 row-cols-md-2 g-4">
        <div class="col">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Recent activity</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-sm">
                            <thead>
                                <tr>
                                    <th>Member</th>
                                    <th>Activity</th>
                                    <th>Status</th>
                                    <th>When</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach (activity as row)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ row.name }}</div>
                                            <div class="text-muted small">{{ row.email }}</div>
                                        </td>
                                        <td class="text-muted">{{ row.action }}</td>
                                        <td>
                                            @if (row.tone == 'success')
                                                <span class="badge bg-success">{{ row.status }}</span>
                                            @elseif (row.tone == 'pending')
                                                <span class="badge bg-warning text-dark">{{ row.status }}</span>
                                            @else
                                                <span class="badge bg-danger">{{ row.status }}</span>
                                            @endif
                                        </td>
                                        <td class="text-muted small">{{ row.when }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Getting started</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        @foreach (tasks as task)
                            <li class="list-group-item">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" @if (task.done ?? false) checked @endif disabled>
                                    <span class="@if (task.done ?? false) text-muted text-decoration-line-through @else fw-medium @endif">{{ task.label }}</span>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                    <p class="text-muted small mb-0">Your name and email come from <strong>AuthManager</strong>;
                        the metrics are demo data. Replace them with your own queries.</p>
                </div>
            </div>
        </div>
    </div>
@endsection
