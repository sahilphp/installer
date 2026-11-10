# SahilPHP — Messaging & Background Processing Architecture

**Status:** Phase-1 assessment (architecture only — no code was changed to
create this document).
**Date:** 2026-10-28
**Scope:** Mail, background jobs & queues, notifications, task scheduling,
operational monitoring & failed-job management.

Capability labels used throughout: **existing** (implemented and covered by
passing tests), **partially implemented** (core works, named gaps remain),
**proposed** (designed here, not yet built), **blocked by a prerequisite**
(design is held back by a missing or immature dependency).

---

## 1. Existing architecture findings

### 1.1 Inspection results (actual repository, not assumptions)

| Area | What exists |
|------|-------------|
| `composer.json` | PHP `^8.2`, runtime deps: `psr/container ^2.0`, `symfony/mailer ^7.4`, `symfony/mime ^7.4`. Dev: PHPUnit 12, PHPStan, PHP-CS-Fixer. PSR-4: `Sahil\` → `src/`, `App\` → `app/`, `Sahil\Tests\` → `tests/`; `src/Support/helpers.php` file-autoloaded. |
| Container | `Sahil\Container\Container` (PSR-11): `bind`/`singleton`/`scoped`, auto-wiring, aliases. Providers in `app/Providers/*` register services lazily. |
| Configuration | Dot-notation `Sahil\Config\Repository`, `env()` helper parsing `.env`, per-environment override files, `Redactor` for secrets, boot-time validation. |
| Database | PDO layer (`Sahil\Database\*`): query builder with bound parameters, transactions with savepoint nesting, private `transactionDepth` tracking, driver-agnostic schema builder, migrations with ledger + advisory locking (MySQL + PostgreSQL). |
| ORM | Active-record `Sahil\Orm\Model` with relations, eager loading, pagination. Documented limits: no polymorphic relations, no `lastInsertId()` backfill on PostgreSQL. |
| HTTP / errors | Kernel with middleware pipeline, central exception handler, `Response`/`Request` value objects. |
| Logging | `Sahil\Support\Logging\{LoggerContract, FileLogger}` — JSON-lines, level threshold, context redaction. |
| View | `Sahil\View\{ViewEngine, TemplateResolver, TemplateCompiler, Escaper}` with `ViewContract` bound by `ViewProvider` (alias `view`). |
| Console | `sahil` — the real CLI (procedural): `about`, `config:show`, `env:check`, `migrate*`, `db:seed`, `queue:work/failed/retry/flush`, `schedule:list/run`. |
| Events | **No event system exists** anywhere in the framework (no dispatcher, no listener registry). |

### 1.2 Test results (recorded, unmodified)

```
Full suite:   742 tests, 1911 assertions →
              1 error, 16 failures, 1 warning, 1 deprecation,
              11 PHPUnit notices, 21 skipped.
Messaging:    tests/Mail + tests/Queue + tests/Notifications + tests/Scheduler
              → 180 tests, 558 assertions → ALL PASS (OK).
Skipped:      21 database-integration tests gated on SAHIL_DB_TEST_MYSQL /
              SAHIL_DB_TEST_PGSQL (credentials not provided in this environment).
Environment:  PHP 8.4.27, pdo_mysql, pdo_pgsql, pcntl all available.
```

The 17 non-skipping failures are **pre-existing and outside this document's
scope** (verified by running the suite before/after unrelated edits):

- `tests/Http/ApiTest.php` — 13 failures + 1 error + 1 warning (request-id
  middleware not wired in test bootstrap, CORS expectations, TRACE method,
  content-type handling).
- `tests/Bootstrap/HttpEntryPointTest.php` — 2 failures (built-in-server
  health/404 endpoints return 500).
- `tests/View/ExpressionTest.php` — 3 failures (expression compiler quotes
  paths; test expects unquoted).

### 1.3 Subsystem inventory with capability status

#### Mail — **existing** (Phase 17, per README; tests green)

| Piece | File |
|-------|------|
| Contracts | `src/Contracts/Mail/{MailerContract, TransportContract}.php` |
| Core | `src/Mail/{MailManager, Message, Address, Html, MessageRenderer, MailReceipt, MailStatus, Sanitizer, InvalidMessageException, MailException, TransportException}.php` |
| Transports | `src/Mail/Transports/{SmtpTransport, LogTransport, ArrayTransport}.php` |
| Queued send | `src/Mail/Jobs/SendMailJob.php` (via `MailerContract::queue()`, gated by `MAIL_QUEUE`) |
| Wiring | `app/Providers/MailProvider.php`, `config/mail.php` (`MAIL_*` env vars) |
| Tests | `tests/Mail/*` (15 files incl. `SmtpDialogueTest` against a scripted SMTP server) |

Delivers: to/cc/bcc/reply-to, HTML + text (`multipart/alternative`, alternate
part auto-generated), header-injection-safe construction, lazy transports,
`MailManager::extend()` for adapters, content-free logging, redacted
credentials, honest receipt states (`queued` ≠ `sent` ≠ `accepted` ≠
`delivered`).

**Gaps:** no attachments; no reusable templates/layouts (the view layer that
could host them already exists); no provider-API transport shipped (the
`TransportContract` + `extend()` extension point exists).

#### Jobs & queues — **existing** (Phase 10; tests green)

| Piece | File |
|-------|------|
| Contracts | `src/Contracts/Queue/{QueueContract, DispatcherContract, FailedJobRepositoryContract, QueuedJob, QueueException}.php`, `src/Contracts/Queue/Jobs/JobContract.php` |
| Core | `src/Queue/{QueueManager, DatabaseQueue, Worker}.php`, `src/Queue/Jobs/Job.php`, `src/Queue/Failed/{FailedJob, FailedJobRepository}.php` |
| Schema | `database/migrations/2026_10_28_000000_create_jobs_table.php`, `..._000001_create_failed_jobs_table.php` |
| CLI | `sahil queue:work [--once|--max=N|--queue=]`, `queue:failed`, `queue:retry <id>|\--all`, `queue:flush` |
| Wiring | `app/Providers/QueueProvider.php`, `config/queue.php` (`QUEUE_*` env vars) |
| Tests | `tests/Queue/*` (7 files + in-memory fakes) |

Delivers: durable database queue, atomic reservation
(`SELECT … FOR UPDATE` + attempt increment inside one transaction), random
16-byte hex job ids (portable — no `lastInsertId()` dependency, so both
MySQL and PostgreSQL work), delayed jobs, per-job `maxAttempts()`/`retryDelay()`
with worker defaults, failed-job store + replay, corrupt-payload containment,
injectable clock.

**Gaps found by code inspection (defects, not just missing features):**

1. **Dead configuration:** `config/queue.php` defines `queue.retry.timeout`
   (`QUEUE_TIMEOUT`, default 60) but `Worker` never enforces it — no job
   timeout exists.
2. **Stale-reservation leak:** `DatabaseQueue::pop()` only selects rows with
   `reserved_at IS NULL`. A worker that crashes mid-job leaves
   `reserved_at` set forever and **the job is never retried at all** — the
   README claim that a crash "consumes an attempt rather than retrying
   forever" overstates the current behaviour (attempts are counted, but the
   row is unreachable). No reservation-reclaim path exists.
3. **No `sync` driver:** there is no first-class synchronous connection;
   tests rely on hand-written in-memory fakes instead of a shippable
   `sync` mode for development.
4. **Not extensible:** `QueueManager::store()` hard-codes
   `driver !== 'database'` → exception. Unlike `MailManager::extend()`,
   there is no factory registry, so adding Redis requires editing the
   framework class.
5. **No dependency injection at execution:** `Worker::resolve()` unserializes
   and calls `handle()` with no container. (Dispatch *is* container-aware —
   `Job::dispatch()` resolves `DispatcherContract` from `app()`. Asymmetric.)
6. **No after-commit dispatch:** dispatching inside a `transaction()` can
   enqueue work for rows a rollback removes. `Connection` tracks
   `transactionDepth` privately — the hook for after-commit dispatch is
   half-present but unused.
7. **No graceful shutdown / signals:** `pcntl` is available in this
   environment but unused — no SIGTERM handling, no memory ceiling, no
   heartbeat for supervisors.
8. **No lifecycle hooks:** nowhere to observe `job.processing`,
   `job.failed`, `job.released` (and no event system to carry them).
9. **No payload guards:** native `serialize()` of arbitrary job objects with
   no size cap and no integrity check (acceptable only while payloads are
   server-generated — must be stated as a trust assumption).

#### Notifications — **partially implemented** (tests green for what exists)

| Piece | File |
|-------|------|
| Contracts | `src/Contracts/Notifications/{NotificationContract, MailNotificationContract, NotifiableContract, ChannelContract}.php` |
| Core | `src/Notifications/{Notifier, Notification, NotificationReport, NotificationResult, NotificationException}.php` |
| Channel | `src/Notifications/Channels/MailChannel.php` (sync or queued via the mail queue) |
| Tests | `tests/Notifications/*` (7 files with recording-channel fakes) |

Delivers: `via()` channel selection, registered channels by name,
per-channel isolation (a failing channel is logged content-free and the rest
still run), unknown-channel = loud configuration error, skip when a notifiable
has no route, report with `accepted|queued|skipped|failed` results.

**Gaps:** no database channel (no storage, no migration, no read/unread
tracking, no listing API); no per-recipient preferences; no queued execution
for non-mail channels; no bulk send helper; no SMS/webhook/push channels
(extension point exists).

**Prerequisite check:** database notifications need a model + migration. The
ORM (Phase 8) exists and is tested, so the prerequisite is **met**, with two
documented constraints: the ORM has **no polymorphic relations** (the
notifiable reference must be explicit `notifiable_type` + `notifiable_id`
columns queried through `Model::query()->where(...)`, not a relation), and
ORM `create()` cannot backfill ids on PostgreSQL (provide an explicit id).

#### Task scheduler — **existing** (tests green)

| Piece | File |
|-------|------|
| Core | `src/Scheduler/{Schedule, Task, ScheduleRunner, CronExpression, FileLock, DatabaseLock, TimeoutGuard, SystemClock, TaskResult, ScheduleReport, SchedulerException, TaskTimeoutException}.php` |
| Contracts | `src/Contracts/Scheduler/{ClockContract, LockContract}.php` |
| Definition | `routes/schedule.php` (declarative), `config/schedule.php` (`SCHEDULE_*`) |
| Wiring | `app/Providers/ScheduleProvider.php` (lazy) |
| CLI | `sahil schedule:list [--at=…]`, `schedule:run [--at=…] [--only=…]` |
| Tests | `tests/Scheduler/*` (10 files, incl. frozen-clock and DST cases) |

Delivers: cron + interval cadence, timezones with DST wall-clock semantics,
per-task overlap locks (file `flock` or database advisory locks for
multi-host), real timeouts via `pcntl` SIGALRM when available, failure
reporting with exit codes, `--at` deterministic evaluation, no back-fill of
missed minutes (standard cron semantics).

**Gaps:** no first-class *job dispatch* helper — tasks are plain in-process
closures; enqueuing work currently requires the closure to resolve the
dispatcher itself (possible, but not an API). No metrics/heartbeat output
beyond `ScheduleReport`.

#### Operational monitoring & failed jobs — **partially implemented**

**Existing:** `queue:failed` (id, queue, time, reason), `queue:retry` (single
or `--all`), `queue:flush`; structured content-free logs (`mail.*`,
`notification.failed`, scheduler failures in `ScheduleReport` + logger);
scheduler exit codes for cron/systemd alerting.

**Gaps:** no `queue:stats`/depth command; no worker heartbeat for
supervisors; no in-app inspection API; no alerting hooks (no event system);
nothing exports metrics (Prometheus-style or otherwise). These are
observability *additions*, not prerequisites.

### 1.4 Duplicated responsibilities & compatibility risks found

1. **Orphaned console engine — `src/Console/`:** a second, incomplete CLI
   layer (`Application`, `Input`, `Output`, `Commands/*`) that `sahil`
   does **not** use (no references), and no tests cover it. Concrete
   incompatibilities:
   - `Console\Application::getWorker()` constructs `Worker` with
     `Connection` as the 3rd positional argument — the real signature wants
     `string $queueName` → `TypeError` at runtime.
   - It resolves `$this->container->make('queue')`, but `QueueProvider`
     binds `QueueContract::class` and defines **no** `queue` alias → binding
     error.
   - The base `Command` class is buried inside `QueueCommand.php`
     (`abstract class Command` at line 11) — file/class mismatch.
   - Command names (`queue:work`, `queue:run`, `queue:dispatch`, …)
     duplicate/overlap the working `sahil` surface.
   **Recommendation:** delete or fully migrate onto `sahil` in a
   dedicated cleanup phase. Do not build new features on it.
2. **README drift:** README says the view layer is "still to come" although
   `src/View` exists (and `tests/View/ExpressionTest` fails — pre-existing).
3. **Two schedule definitions:** `config/schedule.php` (settings) and
   `routes/schedule.php` (tasks) is a *correct* split; noted only because
   the naming invites confusion — keep it, document it.

---

## 2. Proposed subsystem boundaries

Boundaries follow the conventions already in the repository: contracts in
`src/Contracts/<feature>/`, implementations in `src/feature/`, wiring in
`app/Providers/<feature>Provider.php`, settings in `config/<feature>.php`,
CLI in `sahil`, tests in `tests/<feature>/`.

### 2.1 Mail — extend in place

```
src/Contracts/Mail/            MailerContract, TransportContract        [existing]
src/Mail/                      MailManager, Message, Address, Html,
                               MessageRenderer, MailReceipt, MailStatus,
                               Sanitizer, exceptions                    [existing]
src/Mail/Transports/           Smtp, Log, Array                         [existing]
src/Mail/Jobs/SendMailJob.php                                          [existing]
src/Mail/Attachment.php                                                 [proposed]
src/Mail/Templates/            MailTemplate, TemplateRenderer           [proposed]
```

- `Attachment` is a value object on `Message` (`Message::attach()` /
  `attachPath()`), validated at send time (see §10).
- Templates render through the **existing** `ViewContract` (no new template
  engine): `Message::view('mail.welcome', $data)` produces the text and/or
  HTML parts via `ViewEngine` + `Escaper`.
- Provider/API transports stay outside core: they implement
  `TransportContract` and register with `MailManager::extend()`. **No new
  Composer dependency is required for the framework itself** (the provider's
  own SDK, if any, belongs to the application).

### 2.2 Jobs & queues — harden in place, make pluggable

```
src/Contracts/Queue/           QueueContract, DispatcherContract,
                               FailedJobRepositoryContract, JobContract  [existing]
src/Queue/                     QueueManager, DatabaseQueue, Worker,
                               Jobs/Job, Failed/*                       [existing]
src/Queue/SyncQueue.php                                                 [proposed]
src/Queue/JobContext.php (optional container-resolver)                  [proposed]
```

- `QueueManager` gains `extend(string $driver, Closure $factory)` mirroring
  `MailManager::extend()`; the built-in `database` driver becomes the
  default factory, `sync` a second built-in, `redis` an application-level
  registration (see driver comparison §5 — Redis is **proposed/blocked** on
  a dependency decision).
- `Worker` gains (all proposed): enforced `queue.retry.timeout`, stale
  reservation reclaim, signal-aware shutdown, memory ceiling, optional
  container-based invocation of `handle()`, and lifecycle callbacks.

### 2.3 Notifications — add storage and preferences on top of existing contracts

```
src/Contracts/Notifications/   NotificationContract, ChannelContract,
                               MailNotificationContract,
                               NotifiableContract                      [existing]
src/Notifications/             Notifier, Notification, report types    [existing]
src/Notifications/Channels/    MailChannel                              [existing]
                               DatabaseChannel                          [proposed]
src/Notifications/DatabaseNotification.php (ORM model)                 [proposed]
src/Notifications/StoresNotifications.php (trait for models)           [proposed]
src/Contracts/Notifications/PreferencesContract.php                    [proposed]
database/migrations/           create_notifications_table              [proposed]
```

- The `Notifier` is unchanged in its routing contract; `DatabaseChannel` is
  registered exactly like `MailChannel` (`Notifier::extend()`).
- Preferences are checked **by the Notifier** (one place), not by each
  channel, via an optional `PreferencesContract` — additive, defaults to
  "allow all" so existing behaviour is untouched.
- `NotifiableContract` stays as-is; the trait is convenience for ORM models.

### 2.4 Scheduling — add a dispatch helper only

```
src/Scheduler/                 Schedule, Task, ScheduleRunner, locks,
                               CronExpression, TimeoutGuard             [existing]
Task::dispatch(JobContract) / Schedule-level dispatcher binding          [proposed]
```

The scheduler remains queue-agnostic: the helper resolves
`DispatcherContract` from the container **at run time** inside the task, so
a scheduler-only installation with no queue never touches queue services.

### 2.5 Operations — commands, no new subsystem

- `queue:stats` (depth/oldest/reserved/failed per queue) — **proposed**,
  built on `QueueContract::size()` + `isEmpty()` and one count query.
- Worker heartbeat row/file — **proposed, optional**.
- Alerting/metrics exporters — **blocked by a prerequisite**: there is no
  event system; a minimal queue/scheduler listener registry (§8) would be
  the prerequisite. Do not invent a general event bus in this phase.

---

## 3. Dependency and data-flow diagrams

### 3.1 Current data flow (existing)

```
                        ┌──────────────────────────────────────────────┐
                        │                Application code              │
                        └───────┬──────────────┬──────────────┬────────┘
                                │              │              │
             MailerContract::send/queue   Job::dispatch   Notifier::send
                                │              │              │
                ┌───────────────▼──┐   ┌───────▼────────┐     │
                │   MailManager    │   │ QueueManager   │     │
                │  (transport      │   │ (serialize,    │     │
                │   registry,      │   │  pushOn/push)  │     │
                │   validate, log) │   └───────┬────────┘     │
                └───┬────────┬─────┘           │              │
        send()      │        │ queue()         │              │
   ┌────────────────▼─┐  ┌───▼──────────────┐  │      ┌───────▼──────────────┐
   │ TransportContract│  │ SendMailJob      │  │      │  ChannelContract     │
   │ smtp | log|array │  │ (delayed)        ├──┼─────▶│  mail (→ Mailer)     │
   └──────────────────┘  └──────────────────┘  │      │  database (proposed) │
                                               │      └──────────────────────┘
                                    ┌──────────▼───────────┐
                                    │  jobs table (DB)     │◄──── sahil
                                    │  push/pop FOR UPDATE │      queue:work
                                    └──────────┬───────────┘
                                    ┌──────────▼───────────┐
                                    │       Worker         │──failure──▶ failed_jobs
                                    │  handle() / retry /  │             queue:failed
                                    │  release / delete    │             queue:retry
                                    └──────────────────────┘

  sahil schedule:run ─▶ ScheduleRunner ─▶ due Task closures (in-process)
                                 │                  │
                                 │                  └──(proposed)──▶ DispatcherContract
                                 ├── FileLock (flock) / DatabaseLock (advisory)
                                 └── TimeoutGuard (SIGALRM when pcntl)
```

### 3.2 Proposed additions (marked *new*)

```
Application code
   │
   ├─ Message::attach(...) ──────────────▶ [new Attachment validation] ─┐
   ├─ Message::view('mail.x', $data) ───▶ [new MailTemplate]           │
   │                                              │ uses ViewContract  │
   │                                              ▼ (existing Escaper) │
   ├─ MailManager::send ───────────────────────────────────────────────┘
   │
   ├─ (new) sync driver: QueueManager::store('sync') ─▶ executes inline
   ├─ (new) extend('redis', factory) ─▶ RedisQueue (blocked: dep choice)
   │
   ├─ DispatcherContract::afterCommit(job) ─▶ [new] Connection depth hook
   │        └─ enqueues only on commit(); dropped on rollback
   │
   ├─ Worker (new hardening):
   │      timeout enforcement (existing QUEUE_TIMEOUT config)
   │      stale-reservation reclaim (reserve_timeout)
   │      SIGTERM/SIGINT graceful stop + memory ceiling
   │      container-invoked handle() (optional JobContext)
   │      lifecycle callbacks (processed/released/failed)
   │
   ├─ Notifier::send ─▶ (new) DatabaseChannel ─▶ notifications table
   │                └─ (new) PreferencesContract check (opt-out)
   │
   └─ Task::dispatch(job) ─▶ DispatcherContract (resolved lazily at run time)
```

---

## 4. Proposed public API examples

> **These are proposed contracts, not existing functionality** — except
> where marked *existing*, in which case the signature is taken from the
> current code. All examples follow the repository's container conventions
> (`app()->container()->make(...)`, contract-first bindings).

### 4.1 Sending an email

*Existing (works today):*

```php
use Sahil\Contracts\Mail\MailerContract;
use Sahil\Mail\Message;

$receipt = app()->container()->make(MailerContract::class)->send(
    Message::make()
        ->to('ada@example.com', 'Ada')
        ->subject('Your account is ready')
        ->text("Hello Ada,\n\nYour account is ready.")
        ->html('<p>Hello '.Html::escape($user->name).'</p>')
);
// $receipt->status === MailStatus::Accepted
```

*Proposed additions:*

```php
Message::make()
    ->to($user->email)
    ->subject('Invoice '.$invoice->number)
    ->view('mail.invoice', ['invoice' => $invoice])   // renders text + HTML
    ->attachPath($invoice->pdfPath(), ['max_size' => 5 * 1024 * 1024]);

// queued delivery (existing API, MAIL_QUEUE=true):
$mailer->queue($message, delay: 30);
```

### 4.2 Dispatching a background job

*Existing:*

```php
use Sahil\Queue\Jobs\Job;

final class RebuildSearchIndex extends Job
{
    public function __construct(private readonly int $tenantId) {}

    public function handle(): void
    {
        // plain data only — serialized whole
    }

    public function retryDelay(int $attempt): ?int
    {
        return 2 ** $attempt;                 // exponential backoff
    }
}

$id = (new RebuildSearchIndex($tenant->id))->onQueue('search')->dispatch();
$id = (new RebuildSearchIndex(7))->dispatch(delay: 60);
```

*Proposed — after-commit dispatch:*

```php
$db->transaction(function () use ($tenant): void {
    $db->table('tenants')->insert([...]);
    (new RebuildSearchIndex($tenant->id))->dispatchAfterCommit(); // enqueued only if committed
});
```

### 4.3 Executing a job synchronously during testing

*Proposed (no `sync` connection exists yet):*

```php
// config/queue.php — application sets:
'connections' => ['sync' => ['driver' => 'sync']],

// in phpunit.xml / test bootstrap:
env QUEUE_CONNECTION=sync   // or config override

$job->dispatch();            // runs handle() inline, returns an immediate id
```

Until that lands, tests execute jobs through the shipped fakes
(`tests/Queue/InMemoryQueueStore.php` + `Worker` with an injected clock) —
the pattern used by `tests/Queue/WorkerTest.php` *existing*.

### 4.4 Sending a notification through selected channels

*Existing:*

```php
use Sahil\Notifications\Notifier;

final class InvoiceReady extends Notification implements MailNotificationContract
{
    public function via(NotifiableContract $n): array { return ['mail']; }
    public function toMail(NotifiableContract $n): Message { /* ... */ }
}

$report = app()->container()->make(Notifier::class)
    ->send($user, new InvoiceReady('INV-2026-001'));

$report->failed();                                 // bool
$report->forChannel('mail')?->status;              // accepted | queued | skipped | failed
```

*Proposed — database channel + preferences:*

```php
final class InvoiceReady extends Notification implements MailNotificationContract
{
    public function via(NotifiableContract $n): array
    {
        return ['mail', 'database'];           // database is the new channel
    }
}

// Notifier consults the bound PreferencesContract (default: allow all),
// so a notifiable that opted out of 'mail' is reported as 'skipped'.
```

### 4.5 Retrieving and marking database notifications as read

*Proposed (depends on the DatabaseChannel phase):*

```php
// on an ORM model using the StoresNotifications trait:
$unread = $user->unreadNotifications();          // OrmResult of DatabaseNotification
$user->markNotificationsAsRead($ids);            // sets read_at
$user->markAllNotificationsAsRead();
$notification->readAt;                           // null | 'Y-m-d H:i:s'
```

### 4.6 Registering a scheduled task

*Existing:*

```php
// routes/schedule.php
return static function (Schedule $schedule): void {
    $schedule->call('prune-exports', static function (): void {
        // delete files, send reports, ...
    })
        ->dailyAt('03:30')
        ->inTimezone('Europe/Berlin')
        ->withoutOverlapping()
        ->timeoutAfter(300);
};
```

*Proposed — dispatch a queued job instead of running inline:*

```php
$schedule->call('rebuild-search', static function (): void {
    (new RebuildSearchIndex(0))->dispatch();
})->hourly()->withoutOverlapping();
// thin task, heavy work executed by the queue worker
```

### 4.7 Running a queue worker

*Existing:*

```bash
sahil queue:work                              # until interrupted
sahil queue:work --once                       # one job
sahil queue:work --queue=emails --max=100
```

*Proposed flags (Phase A):* `--timeout` override, graceful SIGTERM stop
(finish current job), non-zero exit when the memory ceiling is hit so a
supervisor restarts the process.

### 4.8 Inspecting and retrying failed jobs

*Existing:*

```bash
sahil queue:failed                 # id, queue, time, reason
sahil queue:retry <id>             # replay one
sahil queue:retry --all            # replay everything on the queue
sahil queue:flush                  # forget failures (destructive, gated)
```

*Proposed:* `sahil queue:stats` — per-queue depth, ready/delayed/
reserved counts, oldest ready age, failed count.

---

## 5. Queue-driver comparison

| Property | `database` (**existing**) | `sync` (**proposed**) | `redis` (**proposed — blocked by dependency decision**) |
|---|---|---|---|
| Durability | Rows survive restarts; plain SQL inspection | None (in-process, immediate) | Depends on AOF/RDB persistence config |
| Atomic reservation | `SELECT … FOR UPDATE` in a transaction — proven on MySQL + PostgreSQL | n/a (direct call) | Atomic `RPOPLPUSH`/`LMOVE`-style claim or Lua script |
| Delayed jobs | `available_at` column | Not supported (reject `delay > 0` loudly) | Sorted sets (native) |
| Attempt counting | Column incremented at reservation | Trivial (no reservation) | In the claimed payload / hash |
| Crash safety | **Leak today** (stale `reserved_at` never reclaimed — §1.3) → fix in Phase A | Nothing to leak | Reclaim via visibility-timeout sweep (needs design) |
| Throughput | Lowest (row locks, one worker serializes per queue) | Highest (no IPC) | Highest of durable options |
| Multi-host workers | Yes, via DB locks | No | Yes, native |
| At-least-once | **Yes** (once reclaim lands) | Degenerate (call returns only after run) | Yes, with reclaim sweep |
| New dependency | None | None | `predis/predis` (pure PHP) or `ext-redis` — **user decision required** |
| When to use | Default; modest volume; zero extra infra | Development, tests, CLI one-shots | High volume, low latency, multiple hosts |

**Guarantee statement (mandatory wording for docs):** all drivers offer
**at-least-once** execution. A job may run more than once (worker crash
after side effect but before `delete`). **Exactly-once is not promised and
must not be promised** — handlers must be idempotent or use an
application-level idempotency key. `sync` is the only mode where "once" is
structural, because the caller waits for the result.

---

## 6. Configuration and environment-variable strategy

Existing pattern (keep — config files read `env()`, real secrets only in
`.env`/environment, `config:show` redacts):

### 6.1 Existing (no changes)

| Variable | Purpose | Default |
|---|---|---|
| `MAIL_TRANSPORT` | `smtp` \| `log` \| `array` | `log` |
| `MAIL_HOST/PORT/SECURITY/EHLO_DOMAIN/TIMEOUT` | SMTP endpoint | `127.0.0.1:587`, `tls` |
| `MAIL_USERNAME/MAIL_PASSWORD` | SMTP credentials (both or neither) | empty |
| `MAIL_FROM_ADDRESS/MAIL_FROM_NAME` | Sender identity | — / `SahilPHP` |
| `MAIL_QUEUE` / `MAIL_QUEUE_NAME` | Deferred delivery | `false` / `emails` |
| `QUEUE_CONNECTION` | Queue connection name | `database` |
| `QUEUE_NAME` / `QUEUE_SLEEP` | Worker queue / poll sleep | `default` / `1` |
| `QUEUE_MAX_ATTEMPTS` / `QUEUE_RETRY_DELAY` | Attempt budget / base delay | `3` / `0` |
| `QUEUE_TIMEOUT` | **Defined but currently unenforced** (dead key — Phase A wires it) | `60` |
| `QUEUE_TABLE` / `FAILED_JOB_TABLE` | Table names | `jobs` / `failed_jobs` |
| `SCHEDULE_TIMEZONE` | Default task timezone | `APP_TIMEZONE` |
| `SCHEDULE_LOCK` | `file` \| `database` | `file` |
| `SCHEDULE_LOCK_PATH` / `SCHEDULE_LOCK_PREFIX` | Lock locations | — / `sahil_schedule` |

### 6.2 Proposed new keys (all optional; none required for existing features)

| Variable | Purpose | Default |
|---|---|---|
| `QUEUE_RESERVE_TIMEOUT` | Seconds before a stale reservation is reclaimed by another worker | `600` |
| `QUEUE_MAX_MEMORY_BYTES` (or % of `memory_limit`) | Worker exits for supervisor restart above this | `90%` of limit |
| `QUEUE_DRIVER_REGISTRY` | n/a — drivers are code-registered via `QueueManager::extend()` (no env sprawl) | — |
| `REDIS_URL` / `REDIS_PREFIX` / `REDIS_QUEUE` | Only if Redis driver is approved | — |
| `NOTIFICATIONS_DEFAULT_CHANNELS` | Channels used when `via()` returns none | `['mail','database']` |
| `MAIL_MAX_ATTACHMENT_BYTES` | Attachment cap | `10485760` |

Rules (mandatory): credentials never appear in `config/*.php` literals or
logs (existing `Sanitizer`/`Redactor` must cover every new log line); every
new key is documented in `.env.example`; every new config file gets boot
validation (existing pattern in `Config` repository).

---

## 7. Database tables and migration requirements

### 7.1 Existing (shipped migrations)

```sql
-- database/migrations/2026_10_28_000000_create_jobs_table.php
jobs (id varchar(64) PK, queue varchar, payload text, attempts int default 0,
      available_at timestamp, reserved_at timestamp NULL, created_at NULL,
      updated_at NULL, INDEX(queue))

-- database/migrations/2026_10_28_000001_create_failed_jobs_table.php
failed_jobs (id varchar(64) PK, queue varchar, payload text, exception text,
             failed_at timestamp, INDEX(queue))
```

Both portable across MySQL/PostgreSQL (no `lastInsertId()` dependency — ids
are `bin2hex(random_bytes(16))`).

### 7.2 Proposed additions

```sql
-- 2026_11_01_000000_create_notifications_table.php  [proposed]
notifications (
  id             varchar(64)  primary key,     -- random hex, PG-safe
  notifiable_type varchar(190),                -- FQCN; ORM has NO polymorphic relations
  notifiable_id   varchar(64),                 -- string-safe (int|string pks)
  channel         varchar(64),
  data            text,                        -- JSON payload (view + params, never secrets)
  read_at         timestamp NULL,
  created_at      timestamp NULL,
  INDEX (notifiable_type, notifiable_id),
  INDEX (read_at)
)
```

Notes:
- Explicit string id avoids the ORM's PostgreSQL `lastInsertId()` limit.
- No foreign key to heterogeneous notifiable tables (by design — morph keys
  are application-validated).
- Optional future tables: `job_batches` (grouping), `worker_heartbeats`
  (ops) — both **optional**, not prerequisites.
- No schema changes are needed for mail or scheduler phases.

---

## 8. Error handling and retry policies

### 8.1 Existing policies (verified in code)

| Concern | Behaviour today |
|---|---|
| Job failure, attempts remain | `release()` with `retryDelay(attempt)` (job) or `queue.retry.delay` (worker) |
| Attempt budget exhausted | Payload + message → `failed_jobs`, row deleted from `jobs` |
| Corrupt payload | Error handler contained; recorded as failed, never retried |
| Unreadable payload class | Same as corrupt (recorded, deleted) |
| Mail transport failure | Wrapped in `TransportException`, logged content-free, rethrown |
| `queue()` while disabled | Loud `MailException` (never silent send/drop) |
| Notification channel failure | Logged (`notification.failed`), result `failed`, other channels continue |
| Unknown notification channel | Loud `NotificationException` (wiring bug) |
| Scheduler task throws | Recorded in `ScheduleReport`, logged at `error`, remaining tasks still run, exit 1 |
| Scheduler overlap | Lock taken without waiting; always released in `finally` |
| Scheduler timeout | SIGALRM when `pcntl` present; measured overrun reported otherwise |

### 8.2 Proposed policies (Phase A–C)

1. **Job timeout enforcement** — wire the already-declared
   `queue.retry.timeout`: run `handle()` under the existing
   `Scheduler\TimeoutGuard` SIGALRM technique (reuse, do not duplicate);
   on timeout treat as a failure (release/failed per attempts). If `pcntl`
   is absent, measure and report overrun like the scheduler does — no
   false promise of interruption.
2. **Stale-reservation reclaim** — `pop()` treats rows with
   `reserved_at < now() - QUEUE_RESERVE_TIMEOUT` as available again
   (attempts already counted; capped by `maxAttempts`, so a crash-looping
   job still lands in `failed_jobs`). This is the single most important
   correctness fix in this document.
3. **Retry storms** — jittered backoff helper
   (`retryDelay` presets: `base * 2**attempt ± 20%`), and document that
   `maxAttempts` is per reservation-cycle so reclaim cannot loop forever.
4. **Backpressure** — `queue:stats` + documented ops runbook: if depth
   grows monotonically, scale workers or shed non-critical queues; the
   scheduler must not feed a saturated queue unboundedly (documented
   guidance; no automatic rate control in this phase).
5. **Worker memory** — check `memory_get_usage()` against ceiling between
   jobs; exit non-zero (supervisor restarts) instead of dying mid-job.
6. **Graceful shutdown** — on SIGTERM/SIGINT: stop reserving, finish the
   current job, delete/release correctly, exit 0. Deployment note: rolling
   deploys must send SIGTERM before SIGKILL (systemd `TimeoutStopSec`).
7. **After-commit dispatch** — `dispatchAfterCommit()` enqueues on commit,
   discards on rollback (requires exposing commit callbacks on
   `Connection`; `transactionDepth` already exists but is private).
8. **Malformed payloads from the database** — treat as hostile data: keep
   the current "record and drop" path; never echo payload contents into
   logs beyond a length + hash.

### 8.3 Guarantee table (what we promise vs what depends on the backend)

| Guarantee | Status |
|---|---|
| At-least-once after reservation, once reclaim lands | **Framework** (DB driver) |
| Exactly-once | **Never promised** (documented explicitly) |
| FIFO within a queue | Best effort (`available_at, id` ordering) — a reclaim can reorder |
| Cross-queue fairness | Not provided (one worker = one queue) |
| Order preservation across retries | Not provided |
| Durability | Backend-dependent (DB = transactional; sync = none; Redis = persistence config) |

---

## 9. Testing strategy

### 9.1 Existing (all green)

- `tests/Mail/*` — message validation, address/header injection, HTML
  alternatives, manager behaviour, **real socket SMTP dialogue** against a
  scripted in-process server, sanitiser, provider wiring.
- `tests/Queue/*` — SQL + binding, atomic reservation, attempt counting,
  delay/release, worker retry/failure/corruption, manager serialization;
  `InMemoryQueueStore`, `InMemoryFailedJobs`, `RecordingJob` doubles.
- `tests/Notifications/*` — routing, skip, unknown channel, isolation;
  `RecordingChannel`/`FailingChannel` doubles.
- `tests/Scheduler/*` — cron parsing, DST, due selection, overlap, lock
  release, timeouts; `FrozenClock` + injectable clocks everywhere.
- Determinism patterns already established: injected clocks (`Closure(): DateTimeImmutable`),
  injected locks, in-memory stores, array/log mail transports. **Reuse
  these; do not introduce new test frameworks or mocking libraries.**

### 9.2 Proposed additions (per phase)

| Phase | Tests |
|---|---|
| A: queue hardening | Unit: timeout release path, reclaim SQL per dialect (skip-gated on env like existing integration tests), SIGTERM stop (pcntl-gated), memory ceiling; `sync` driver round trip; `dispatchAfterCommit` commit/rollback matrix |
| B: mail attachments/templates | Attachment validation (size, MIME via finfo, traversal, missing file), template render through `ViewContract` with escaping assertions, multipart output |
| C: database notifications | Migration on MySQL + PostgreSQL (env-gated), channel write/read/unread flows, preferences skip matrix, PG explicit-id path |
| D: scheduler dispatch | Task dispatches job only when due; no queue binding → scheduler still runs (laziness regression test) |
| E: optional drivers/hooks | Redis suite behind env gate `SAHIL_REDIS_TEST_URL`, same pattern as DB tests |

Integration tests must follow the existing convention: skip without
credentials (`SAHIL_DB_TEST_MYSQL` / `SAHIL_DB_TEST_PGSQL`), never
hard-fail in CI without infrastructure.

---

## 10. Security considerations

### 10.1 Mandatory (existing machinery must be reused)

| Requirement | How (existing vs new) |
|---|---|
| Mail/queue credentials out of VCS and logs | **Existing**: `.env` git-ignored, `Redactor`, `Sanitizer::error()`, content-free `mail.*` logs. Rule: every new log line passes through `Sanitizer`. |
| Email content safety | **Existing**: `Html::escape()`, text/HTML alternative generation escapes text; HTML parts are trusted-author-only (documented). Templates must render through `View\Escaper` — never raw-concatenate user data. |
| Attachment safety (**new**) | Path resolved and verified *inside* configured roots (reuse storage rules: no `..`, no symlinks, no NUL); MIME via `finfo` not client-declared; size cap (`MAIL_MAX_ATTACHMENT_BYTES`); executable extensions refused; re-verify after write like `Storage::upload()` does. |
| Job payload trust | **Existing assumption (must be documented loudly):** `unserialize()` of job payloads is only safe while rows are server-written. Mandatory: never accept raw payloads from clients (`pushRaw` is internal). Optional hardening: HMAC-sign payloads with `APP_KEY`-style secret before push, verify before unserialize. |
| Malformed/hostile payloads | **Existing**: contained error handler, record-and-drop, no payload echo in logs. |
| Worker crash / interrupted jobs | **Existing partial** + Phase-A reclaim (§8.2). Reserve→run→ack remains transactional at the reservation boundary. |
| DB transactions & dispatch | **New**: after-commit dispatch (§8.2) prevents workers reading rows that rolled back. |
| Queue starvation / retry storms | §8.2 (jitter, budgets, `queue:stats`). |
| Long-running worker memory | §8.2 memory ceiling + supervisor restart. |
| Graceful shutdown / deploys | §8.2 SIGTERM semantics. |
| Database notification data | Payload must contain only what the channel needs; never store secrets in `data` (goes through the same redaction rules when logged). |
| Future webhook channel | SSRF + secret-in-URL rules apply — noted now so the contract reserves room (allowlist of hosts, no URL from user input). **Optional/future.** |

### 10.2 Optional future enhancements (explicitly not mandatory now)

- HMAC-signed job payloads (defence-in-depth against DB write access).
- Unique-job locks (would reuse the existing `src/Cache` layer).
- Attachment virus scanning hook (pre-send interceptor on `MailManager`).
- Metrics/audit export of `queue:failed` history retention policies.

---

## 11. Implementation phases and dependencies

Dependency-ordered; each phase is independently shippable and must keep the
suite green (`composer check`).

| Phase | Content | Depends on | Status target |
|---|---|---|---|
| **0. Console consolidation** | Delete or migrate orphaned `src/Console/` (§1.4) — removes a latent TypeError and duplicate command names | — (independent, recommended first to stop confusion) | cleanup |
| **A. Queue correctness hardening** | Stale-reservation reclaim; enforce `QUEUE_TIMEOUT`; `sync` driver; `QueueManager::extend()` registry; graceful shutdown + memory ceiling; after-commit dispatch; `queue:stats` | — | **existing → complete** |
| **B. Mail completeness** | `Attachment` (+ validation), templates/layouts via existing `ViewContract`, (optional) one provider-API transport example outside core | B benefits from A's `sync` in dev, but not required | **existing → extended** |
| **C. Notifications storage** | `notifications` migration, `DatabaseChannel`, ORM model + trait, read/unread APIs, `PreferencesContract` in `Notifier`, optional `SendNotificationJob` for queued non-mail channels | ORM (exists); A for queued non-mail channels | **partial → complete** |
| **D. Scheduler ↔ queue bridge** | First-class dispatch inside tasks; docs runbook (cron/systemd) already exist | A (dispatch path), mostly independent | **existing → extended** |
| **E. Observability hooks** | Minimal listener registry for queue/scheduler lifecycle events (no general event bus), heartbeat, log-based alerting examples | A | **proposed** |
| **F. Redis driver (optional)** | `RedisQueue` via `QueueManager::extend()` | A (registry); **blocked by user decision on `predis` vs `ext-redis`** | **blocked** |
| **G. Pre-existing test debt (separate track)** | 17 failures in `tests/Http`, `tests/Bootstrap`, `tests/View` (§1.2) — unrelated to messaging but blocks a green `composer check` | — | repair |

---

## 12. Compatibility risks and unresolved decisions

### 12.1 Compatibility risks

1. **`Worker` constructor is positional and public** — adding parameters
   (e.g. a container resolver) breaks `QueueProvider` and any application
   code constructing it directly. Mitigation: add options as a trailing
   nullable parameter or an options object; keep the current signature
   working.
2. **`Notifier::send` signature is a public contract** — adding preference
   checks must be an *optional constructor dependency* (default null =
   current behaviour), not a new required argument.
3. **`DatabaseQueue::pop()` SQL changes** (reclaim predicate) touch the
   only reservation path — must be integration-tested on **both** drivers;
   `FOR UPDATE` + `LIMIT` ordering is dialect-sensitive.
4. **Native `serialize()` payloads** are already on disk in `jobs` — any
   future signing scheme must tolerate existing unsigned rows (dual-read).
5. **Orphaned `src/Console`** (§1.4) may be autoloaded by accident
   (PSR-4 `Sahil\` covers it); until Phase 0, avoid referencing it.
6. **`config/queue.php`'s dead `timeout` key** — wiring it changes
   behaviour for deployments that set `QUEUE_TIMEOUT` expecting nothing to
   happen; call it out in the changelog.
7. **README overstates crash-retry behaviour** (§1.3 gap 2) — documentation
   must be corrected together with Phase A, not before.

### 12.2 Unresolved decisions (need user/approver input)

| # | Decision | Options | Recommendation |
|---|---|---|---|
| 1 | Redis dependency (Phase F) | `predis/predis` (pure PHP, no ext) vs `ext-redis` (faster, ops burden) vs skip Redis entirely | Default: **skip** until scale demands it; the registry in Phase A makes it pluggable later with no further framework change |
| 2 | Fate of `src/Console/` | Delete vs migrate `sahil` onto it | **Delete** (it is untested and internally incompatible) |
| 3 | Payload signing | None / HMAC with app secret | Optional hardening — decide if untrusted DB access is in the threat model |
| 4 | Execution-time DI model for jobs | (a) keep plain jobs + `app()` inside `handle()`; (b) container-invoked `handle()` params via reflection; (c) explicit `Job::middleware` pipeline | Recommend **(a) now, (b) as an additive Worker option**; (c) only if a real need appears |
| 5 | Event system scope | Full event bus vs minimal queue/scheduler listener registry | **Minimal registry** (Phase E) — a general event bus is a separate initiative |
| 6 | Notification id strategy | Random hex (consistent with jobs) vs DB auto-increment | **Random hex** (PG-safe, consistent with `DatabaseQueue`) |
| 7 | Scheduled-task job dispatch ergonomics | `Task::dispatch()` helper vs documenting closure-based dispatch | Helper (tiny, queue-agnostic by construction) |

---

## Appendix — inspected files

`composer.json`, `sahil`, `phpunit.xml`, `config/{mail,queue,schedule}.php`,
`routes/schedule.php`, `database/migrations/*jobs*`, `app/Providers/{Mail,Queue,Schedule,Auth,Session,View}Provider.php`,
`src/Contracts/Mail/*`, `src/Contracts/Queue/*` (incl. `Jobs/JobContract.php`),
`src/Contracts/Notifications/*`, `src/Contracts/Scheduler/*`,
`src/Mail/*` (incl. `Transports/`, `Jobs/SendMailJob.php`),
`src/Queue/{QueueManager,DatabaseQueue,Worker}.php`, `src/Queue/Jobs/Job.php`,
`src/Queue/Failed/*`, `src/Notifications/*` (incl. `Channels/MailChannel.php`),
`src/Scheduler/{Schedule,Task}.php`, `src/Database/Connection.php`,
`src/Console/*`, `src/View/*` (directory), `tests/{Mail,Queue,Notifications,Scheduler}/*` (listing + suite runs).

**Test commands executed:** `composer test` (full suite), `vendor/bin/phpunit tests/Mail tests/Queue tests/Notifications tests/Scheduler` (subsystem suite), extension availability probe (`pdo_mysql`, `pdo_pgsql`, `pcntl`).
