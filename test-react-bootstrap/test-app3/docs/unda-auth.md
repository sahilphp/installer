# Authentication scaffolding (`php sahil unda:auth`)

`unda:auth` installs a complete, working session-authentication system in one
command. Nothing has to be wired by hand afterwards.

> **Compatibility:** `make:auth` remains available as a hidden alias for this
> command. Use `unda:auth` in new projects and documentation.

```bash
php sahil unda:auth                    # complete implementation
php sahil unda:auth --ui=bootstrap     # choose the page style
php sahil unda:auth --force            # overwrite managed files (backups kept)
php sahil unda:auth --dry-run          # print the plan, write nothing
php sahil unda:auth --path=/path/app   # scaffold another checkout
```

The template extension is detected from `resources/views` (`.sahil.php`,
`.php`, or `.html`); it is never hardcoded.

## Files created

### Database

| File | Columns |
|---|---|
| `database/migrations/<timestamp>_create_users_table.php` | `id` bigint PK, `name` varchar 255, `email` varchar 255 unique, `email_verified_at` timestamp nullable, `password` varchar 255, `remember_token` varchar 100 nullable, `created_at`, `updated_at` |
| `database/migrations/<timestamp>_create_password_reset_tokens_table.php` | `email` varchar 255 PK, `token` varchar 255, `created_at` timestamp nullable |
| `database/migrations/<timestamp>_create_sessions_table.php` | `id` varchar 255 PK, `user_id` bigint nullable indexed, `ip_address` varchar 45 nullable, `user_agent` text nullable, `payload` longtext, `last_activity` int indexed |

If a `*_create_users_table.php` (or the equivalent reset/sessions migration)
already exists in `database/migrations`, that migration is skipped and a
warning is printed. `--force` does not create a duplicate table migration.

### Model

`app/Models/User.php` implements `Sahil\Contracts\Auth\AuthenticatableContract`
with `fillable`, `hidden` (`password`, `remember_token`), casts
(`password => hashed`, `email_verified_at => datetime`),
`getAuthIdentifier()` and `getAuthPassword()`. Passwords are written through
`createWithPassword()` / `setPassword()` only.

### Controllers

| File | Methods |
|---|---|
| `app/Http/Controllers/Auth/LoginController.php` | `showLoginForm()`, `login()`, `logout()` (plus `showDashboard()` for the authenticated landing page) |
| `app/Http/Controllers/Auth/RegisterController.php` | `showRegistrationForm()`, `register()` |
| `app/Http/Controllers/Auth/ForgotPasswordController.php` | `showLinkRequestForm()`, `sendResetLinkEmail()` |
| `app/Http/Controllers/Auth/ResetPasswordController.php` | `showResetForm()`, `reset()` |

Login validates email and password through `AuthManager` (rate limiting,
rehashing, session rotation). Registration validates name, email, password
confirmed and at least 8 characters, creates the user, and signs them in.
Password reset validates the email, mails a single-use link through the mailer,
validates the token, replaces the password, revokes outstanding links, and
signs the user in.

### Middleware

| File | Behaviour |
|---|---|
| `app/Http/Middleware/Authenticate.php` | redirects guests to `/login` |
| `app/Http/Middleware/RedirectIfAuthenticated.php` | redirects signed-in users from guest pages to `/dashboard` |

### Routes

`routes/auth.php` defines:

- guest routes wrapped in `RedirectIfAuthenticated`:
  `GET /login` (`login`), `POST /login`, `GET /register` (`register`),
  `POST /register`, `GET /forgot-password`, `POST /forgot-password`,
  `GET /reset-password/{token}`, `POST /reset-password`
- `POST /logout` behind `Authenticate`
- `GET /dashboard` behind `Authenticate`

`unda:auth` also adds `require __DIR__.'/auth.php';` to `routes/web.php` only
when that reference is not already present.

### Views

`resources/views/auth/login.*`, `register.*`, `forgot-password.*`,
`reset-password.*`, `resources/views/pages/dashboard.*`, and the auth/dashboard
layouts. The extension follows the project's template extension.

## `--force` and safety

- An existing file is skipped and reported unless `--force` is passed.
- `--force` overwrites managed files and copies the previous version to
  `storage/framework/auth-backups/<timestamp>-<random>/`.
- Every generated PHP file is checked with `php -l`; a parse failure aborts the
  command with the file name and the parser output.
- No password, token, or secret is written into any generated file.

## Extending the implementation

The generated files are ordinary application code: edit them in place. Each
controller renders with `ViewContract` and receives `errors`, `old`, the CSRF
token and `app_name`, so adding a field means adding it to the validator rules
and the view.

To customise the starting point for future projects, edit the sources under
`.sahil/scaffolds/auth/` (`shared/` for PHP, `tailwind/` and `bootstrap/` for
views). `unda:auth` copies those trees verbatim (renaming the detected view
extension) and runs the same lint/skip/backup logic.

Add `email_verified_at` handling by writing the timestamp after registration
and guarding sensitive routes with a middleware that checks it; the column and
model cast already exist.
