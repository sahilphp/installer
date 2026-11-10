@extends('layouts.dashboard')

@section('title')Dashboard · {{ app_name ?? 'SahilPHP' }}@endsection

@section('content')
    <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2 mb-4">
        <div>
            <h1 class="h3 fw-semibold mb-1">Overview</h1>
            <p class="text-muted small mb-0">
                Welcome back, {{ user.name }}. Here is what happened this week.
            </p>
        </div>

        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary btn-sm" href="/ui-kit">Components</a>
            <a class="btn btn-primary btn-sm" href="/register">Invite member</a>

            <form method="post" action="/logout" class="d-inline">
                <input type="hidden" name="_token" value="{{ csrf_token }}">
                <button class="btn btn-outline-secondary btn-sm" type="submit">Sign out</button>
            </form>
        </div>
    </div>

    <section class="row g-3" aria-label="Key metrics">
        @foreach (stats as stat)
            <div class="col-sm-6 col-xl-3">
                <article class="card h-100 border-0 shadow-sm rounded-4">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-start justify-content-between">
                            <p class="text-muted small mb-1">{{ stat.label }}</p>

                            @if (stat.direction == 'up')
                                <span class="badge text-bg-success-subtle text-success-emphasis">{{ stat.change }}</span>
                            @else
                                <span class="badge text-bg-danger-subtle text-danger-emphasis">{{ stat.change }}</span>
                            @endif
                        </div>

                        <p class="h4 fw-semibold mb-1">{{ stat.value }}</p>
                        <p class="text-muted mb-0" style="font-size: .72rem;">{{ stat.hint }}</p>
                    </div>
                </article>
            </div>
        @endforeach
    </section>

    <div class="row g-3 mt-1">
        <div class="col-xl-8">
            <section class="card border-0 shadow-sm rounded-4 h-100" aria-labelledby="activity-heading">
                <div class="card-body pb-0">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <h2 class="h6 fw-semibold mb-1" id="activity-heading">Recent activity</h2>
                            <p class="text-muted small mb-0">Team actions across your workspace.</p>
                        </div>
                        <a class="small text-decoration-none" href="#">View all</a>
                    </div>
                </div>

                <div class="table-responsive mt-3">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr class="text-uppercase text-muted" style="font-size: .68rem; letter-spacing: .04em;">
                                <th scope="col" class="ps-3">Member</th>
                                <th scope="col">Activity</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="pe-3">When</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach (activity as row)
                                <tr>
                                    <td class="ps-3">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="rounded-circle bg-secondary-subtle text-secondary-emphasis d-inline-flex align-items-center justify-content-center fw-semibold"
                                                style="width: 2rem; height: 2rem; font-size: .7rem;">
                                                {{ row.initials }}
                                            </span>
                                            <span class="lh-sm">
                                                <span class="d-block fw-medium small">{{ row.name }}</span>
                                                <span class="d-block text-muted" style="font-size: .72rem;">{{ row.email }}</span>
                                            </span>
                                        </div>
                                    </td>

                                    <td class="small text-muted">{{ row.action }}</td>

                                    <td>
                                        @if (row.tone == 'success')
                                            <span class="badge text-bg-success-subtle text-success-emphasis">Success</span>
                                        @elseif (row.tone == 'pending')
                                            <span class="badge text-bg-warning-subtle text-warning-emphasis">Pending</span>
                                        @else
                                            <span class="badge text-bg-danger-subtle text-danger-emphasis">Failed</span>
                                        @endif
                                    </td>

                                    <td class="pe-3 text-muted text-nowrap" style="font-size: .75rem;">{{ row.when }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <div class="col-xl-4">
            <section class="card border-0 shadow-sm rounded-4 h-100" aria-labelledby="tasks-heading">
                <div class="card-body p-3">
                    <h2 class="h6 fw-semibold mb-1" id="tasks-heading">Getting started</h2>
                    <p class="text-muted small mb-3">Finish these to make the scaffold your own.</p>

                    <div class="list-group list-group-flush">
                        @foreach (tasks as task)
                            <label class="list-group-item d-flex align-items-start gap-2 px-0 border-0 py-2 small">
                                <input class="form-check-input mt-1" type="checkbox" @if (task.done ?? false) checked @endif disabled>
                                <span class="@if (task.done ?? false) text-muted text-decoration-line-through @endif">{{ task.label }}</span>
                            </label>
                        @endforeach
                    </div>

                    <div class="alert alert-light border small mb-0 mt-3" role="note">
                        Your name and email come from <code>AuthManager</code>; the metrics are demo data.
                        Replace them with your own queries.
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection
