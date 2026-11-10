# Routes and schedules

- `web.php` — the HTTP route table. Returns an array of `Sahil\Http\Route`
  objects and is loaded by `App\Providers\WebRoutesProvider`.
- `schedule.php` — the task scheduler registry. Returns a callable that
  receives a `Sahil\Scheduler\Schedule` and registers tasks; loaded by
  `App\Providers\ScheduleProvider`. See "Task scheduler" in the README.

Both files are optional in the sense that the framework reports a clear
error when an expected file is malformed; `schedule.php` may be absent, in
which case the schedule is empty.
