# Authentication & Authorization — Secure Production Deployment

This document describes how to deploy the SahilPHP authentication and
authorization layer safely in production, and what the framework itself
guarantees. Read it together with [SESSION_AND_CSRF_SECURITY.md](SESSION_AND_CSRF_SECURITY.md),
which covers session cookies and CSRF in depth.

## What the framework provides

| Component | Location | Guarantee |
|-----------|----------|-----------|
| Password hashing | `Sahil\Auth\Password` | PHP's `password_hash()` (bcrypt, cost 12 by default), `password_verify()`, `password_needs_rehash()` — no custom cryptography |
| Login / logout / session state | `Sahil\Auth\AuthManager`, `Sahil\Auth\SessionAuthState` | Session-ID rotation on login and logout (fixation defence), brute-force rate limiting, transparent rehash on login |
| User providers | `Sahil\Contracts\Auth\UserProviderContract` (e.g. `DatabaseUserProvider`) | Swappable storage; credentials are only ever compared with `password_verify()` |
| Authorization | `Sahil\Auth\Gate`, `Sahil\Auth\Policy`, `Sahil\Auth\PolicyManager` | Callback gates, role/permission checks, per-model policies; default-deny for undefined gates/actions |
| Middleware | `Sahil\Http\Middleware\Authenticate`, `Sahil\Http\Middleware\Authorize` | 401 for guests, 403 for missing ability; the denied handler never runs |
| Password reset | `Sahil\Auth\PasswordResetManager` + `PasswordResetTokenRepositoryContract` | 64-hex-char `random_bytes` tokens, 60-minute expiry, single-use, no user enumeration |
| Email verification | `Sahil\Auth\EmailVerification` + `MustVerifyEmailContract` | Optional integration points only — the framework never sends mail itself |

## Production checklist

### 1. Environment and error handling

```env
APP_ENV=production
APP_DEBUG=false
```

- `APP_DEBUG=false` in production. Debug output can expose configuration,
  credentials and stack traces to attackers.
- Keep `.env` outside source control (it already is — `.env` is
  git-ignored) and restrict its filesystem permissions to the service user
  (`chmod 600 .env`).
- `php sahil config:show` prints every key with `password` values as
  `[redacted]`; use it instead of cat-ing config in shared shells.

### 2. Transport and session cookies

Authentication is only as strong as the channel it travels over.

- Terminate TLS in front of the application and redirect all HTTP to HTTPS.
- Set `SESSION_SECURE_COOKIE=true` so the session cookie is never sent in
  clear text, keep `SESSION_HTTP_ONLY=true` (default), and use
  `SESSION_SAME_SITE=lax` (or `strict` if your flows allow it).
- The session ID is regenerated on **login and logout**, so a session
  fixed before authentication is worthless afterwards. Do not cache pages
  between users behind a shared session.

### 3. CSRF

- Keep `VerifyCsrfToken` enabled for every state-changing route. The
  framework refuses unsafe methods without a valid per-session token and
  rotates the token after each successful validation.
- Only add paths to `CSRF_EXCEPT` for endpoints that cannot carry a token
  (webhooks with their own authentication). Each exclusion is a deliberate
  exception — list them in a code review, not at runtime.

### 4. Password policy and hashing

- Hashing uses PHP's `password_hash()` exclusively. Never store or log a
  plaintext password; the framework does not.
- The default bcrypt cost is **12**. Benchmark on production hardware
  (`password_hash` timing) before raising it; the login path rehashes
  stored passwords automatically when the configured options change, so
  you can raise the cost without a migration.
- Application-level rules (minimum length, breached-password checks) are
  the application's responsibility — enforce them at registration/reset.

### 5. Brute-force protection

- `AuthManager::attempt()` rate-limits per login identifier and returns
  generic failures for both "unknown user" and "wrong password", so the
  responses do not reveal which accounts exist.
- **Known limitation:** the `RateLimiter` keeps its counters in process
  memory. On a single long-running process this is effective; under
  PHP-FPM the limit is per worker. If you run multiple workers or
  instances, put the counter in a shared store (Redis/database) before
  relying on it as your only defence, and additionally front login
  routes with an edge-level rate limit (WAF, nginx `limit_req`).

### 6. Password reset tokens

- Tokens are 32 bytes from `random_bytes()`, expire after 60 minutes,
  and are consumed on first use — a used or expired token is refused.
- Reset responses are identical whether or not the address exists;
  keep it that way in your controllers (do not branch on "user not found").
- Deliver the token over TLS only. Never write it to a log, an error
  report, or a URL query string that lands in access logs — put it in the
  request body or a POST-driven link.
- Store tokens hashed in your `PasswordResetTokenRepositoryContract`
  implementation (compare with `hash_equals`), and prune expired records
  with `deleteExpired()` on a schedule.

### 7. Logging and monitoring

- The framework never logs passwords, reset tokens, session IDs or CSRF
  tokens. Do not add logging of request bodies to authentication routes.
- Log security events without secrets: failed-login counts, lockouts,
  password-reset requests (identifier only, no token).
- Alert on bursts of 401/403 responses — they are your enumeration and
  stuffing signal.

### 8. Email verification (optional)

- If you require verified addresses, implement `MustVerifyEmailContract`
  on your user and gate privileged routes with `EmailVerification`.
  The framework defines the integration points; sending mail is yours.

### 9. Filesystem and session storage

- Session files live under `storage/framework/sessions` (outside the web
  root). Keep the directory owned by the service user, mode `0700`;
  files are written `0600`.
- Application code, `.env`, `storage/` and `database/` must never be
  reachable via the web server document root (`public/` only).

### 10. Ship it

```bash
composer install --no-dev --optimize-autoloader   # no dev tooling in prod
composer check                                    # lint + tests + phpstan

# One gated runner, not every instance at boot:
php sahil migrate --force
```

## Verifying a deployment

The security behaviour above is covered by automated tests — run them on
every deploy candidate:

```bash
vendor/bin/phpunit tests/Auth
# or the full gate:
composer check
```

| Behaviour | Test |
|-----------|------|
| Invalid credentials rejected, no authentication | `AuthManagerTest::test_invalid_credentials_are_rejected_and_do_not_authenticate` |
| Brute-force lockout (correct password refused after repeated failures) | `AuthManagerTest::test_repeated_failures_lock_out_even_the_correct_password` |
| Session-ID rotation on login and logout | `AuthManagerTest::test_login_rotates_the_session_id_against_fixation`, `…test_logout_rotates_the_session_id_and_clears_state` |
| Unauthenticated access → 401, handler never runs | `AuthenticateMiddlewareTest` |
| Permission denial → 403, handler never runs | `AuthorizeMiddlewareTest` |
| Reset token expiry and single use | `PasswordResetTest::test_expired_token_is_refused`, `…test_valid_token_resets_the_password_once` |
| No user enumeration on reset | `PasswordResetTest::test_token_creation_for_unknown_account_returns_no_token` |
| Generic login failures | `AuthManagerTest::test_unknown_identifier_is_rejected_without_distinct_failure` |

## Incident response

- **Suspected account compromise:** invalidate the affected sessions by
  deleting the session store (or the user's session files), force a
  password change, and call `PasswordResetManager::deleteTokens()` to
  burn outstanding reset tokens.
- **Leaked signing material or database:** rotate the database
  credentials, regenerate any exposed tokens, and review audit logs for
  401/403 bursts around the exposure window.
- **Lost reset token repository:** tokens are useless without the
  matching account password — but clear them anyway if the store itself
  was readable, since a readable store plus a compromised mailbox is a
  takeover path.
