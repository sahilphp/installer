@extends('layouts.dashboard')

@section('title')Dashboard · {{ app_name ?? 'SahilPHP' }}@endsection

@section('content')
    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Overview</h1>
            <p class="mt-1 text-sm text-slate-500">
                Welcome back, {{ user.name }}. Here is what happened this week.
            </p>
        </div>

        <div class="mt-3 flex gap-2 sm:mt-0">
            <a class="inline-flex h-9 items-center rounded-md border border-slate-200 bg-white px-3 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
                href="/ui-kit">Components</a>
            <a class="inline-flex h-9 items-center rounded-md bg-primary px-3 text-sm font-semibold text-primary-foreground shadow-sm transition hover:opacity-90"
                href="/register">Invite member</a>

            <form class="inline" method="post" action="/logout">
                <input type="hidden" name="_token" value="{{ csrf_token }}">
                <button class="inline-flex h-9 items-center rounded-md border border-slate-200 bg-white px-3 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
                    type="submit">Sign out</button>
            </form>
        </div>
    </div>

    <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Key metrics">
        @foreach (stats as stat)
            <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-slate-500">{{ stat.label }}</p>

                    <span class="inline-flex items-center gap-1 rounded-md px-1.5 py-0.5 text-[11px] font-medium @if (stat.direction == 'up') bg-emerald-50 text-emerald-700 @else bg-rose-50 text-rose-700 @endif">
                        {{ stat.change }}
                    </span>
                </div>

                <p class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">{{ stat.value }}</p>
                <p class="mt-1 text-xs text-slate-400">{{ stat.hint }}</p>
            </article>
        @endforeach
    </section>

    <div class="mt-6 grid gap-4 lg:grid-cols-3">
        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm lg:col-span-2" aria-labelledby="activity-heading">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900" id="activity-heading">Recent activity</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Team actions across your workspace.</p>
                </div>

                <a class="text-xs font-medium text-primary hover:underline" href="#">View all</a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-[11px] uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-2.5 font-medium" scope="col">Member</th>
                            <th class="px-5 py-2.5 font-medium" scope="col">Activity</th>
                            <th class="px-5 py-2.5 font-medium" scope="col">Status</th>
                            <th class="px-5 py-2.5 font-medium" scope="col">When</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @foreach (activity as row)
                            <tr class="transition hover:bg-slate-50/70">
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-3">
                                        <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-slate-100 text-xs font-semibold text-slate-600">
                                            {{ row.initials }}
                                        </span>
                                        <span class="min-w-0">
                                            <span class="block truncate font-medium text-slate-900">{{ row.name }}</span>
                                            <span class="block truncate text-xs text-slate-500">{{ row.email }}</span>
                                        </span>
                                    </div>
                                </td>

                                <td class="px-5 py-3 text-slate-600">{{ row.action }}</td>

                                <td class="px-5 py-3">
                                    @if (row.tone == 'success')
                                        <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">{{ row.status }}</span>
                                    @elseif (row.tone == 'pending')
                                        <span class="inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700">{{ row.status }}</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700">{{ row.status }}</span>
                                    @endif
                                </td>

                                <td class="px-5 py-3 whitespace-nowrap text-xs text-slate-500">{{ row.when }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm" aria-labelledby="tasks-heading">
            <h2 class="text-sm font-semibold text-slate-900" id="tasks-heading">Getting started</h2>
            <p class="mt-0.5 text-xs text-slate-500">Finish these to make the scaffold your own.</p>

            <ul class="mt-4 space-y-3">
                @foreach (tasks as task)
                    <li class="flex items-start gap-2.5 text-sm">
                        <input class="mt-0.5 h-4 w-4 rounded border-slate-300 text-primary focus:ring-2 focus:ring-primary/30"
                            type="checkbox" @if (task.done ?? false) checked @endif disabled>
                        <span class="@if (task.done ?? false) text-slate-400 line-through @else text-slate-700 @endif">{{ task.label }}</span>
                    </li>
                @endforeach
            </ul>

            <div class="mt-5 rounded-lg bg-slate-50 p-3 text-xs leading-relaxed text-slate-500">
                Your name and email come from <code class="rounded bg-white px-1 py-0.5">AuthManager</code>;
                the metrics are demo data. Replace them with your own queries.
            </div>
        </section>
    </div>
@endsection
