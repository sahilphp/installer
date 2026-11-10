# Session and CSRF Security

This document describes the session and CSRF security features implemented in Phase 11, along with deployment requirements and secure configuration guidelines.

## Overview

SahilPHP provides a secure session management system with:

- **Pluggable session storage** - File-based driver by default with interface for custom drivers
- **Secure cookie attributes** - HttpOnly, Secure, SameSite configured for security
- **Session ID rotation** - Prevents session fixation attacks
- **Session expiration** - Automatic cleanup of expired sessions
- **CSRF protection** - Token-based validation for state-changing HTTP methods
- **Request-scoped isolation** - Sessions are isolated between requests in long-running workers

## Configuration

Session configuration is managed in `config/session.php`. The following settings are available:

### Session Driver

```php
'driver' => env('SESSION_DRIVER', 'file'),
```

Currently only the `file` driver is supported. Additional drivers (Redis, database, etc.) can be implemented by extending the `SessionDriverContract` interface.

### Session Lifetime

```php
'lifetime' => (int) env('SESSION_LIFETIME', 120), // minutes
```

The session lifetime in minutes. After this time, the session will be considered expired and a new session will be created.

**Recommended values:**
- High-security applications: 15-30 minutes
- Standard applications: 60-120 minutes
- Low-security applications: 1440 minutes (24 hours)

### Session Storage Path

```php
'path' => env('SESSION_PATH', storage_path('framework/sessions')),
```

The directory where session files are stored. This directory should:
- Be outside the web root
- Be writable by the web server process
- Have restrictive permissions (0700 for the directory, 0600 for files)

### Session Cookie Name

```php
'cookie' => env('SESSION_COOKIE', 'sahil_session'),
```

The name of the cookie used to store the session ID. This should be unique to your application to avoid conflicts with other applications on the same domain.

### Secure Cookie

```php
'secure' => env('SESSION_SECURE_COOKIE', env('APP_ENV') === 'production'),
```

Whether the session cookie should only be sent over HTTPS.

**IMPORTANT:** Set to `true` in production to prevent cookie theft over unencrypted connections. Set to `false` in local development.

### HTTP Only Cookie

```php
'http_only' => env('SESSION_HTTP_ONLY', true),
```

Whether the session cookie should be inaccessible to JavaScript via `document.cookie`.

**Always keep this enabled** to prevent XSS attacks from stealing session cookies.

### Same-Site Cookie Attribute

```php
'same_site' => env('SESSION_SAME_SITE', 'lax'),
```

SameSite attribute for the session cookie. Controls when cookies are sent with cross-site requests.

**Options:**
- `'lax'` - Cookies sent with top-level navigations (default, recommended)
- `'strict'` - Cookies only sent for same-site requests (most secure)
- `'none'` - Cookies sent with all requests (requires Secure, not recommended)

For most applications, `'lax'` provides the best balance of security and usability. Use `'strict'` for banking or other high-security applications.

### CSRF Exclusions

```php
'csrf_except' => array_filter(
    array_map('trim', explode(',', env('CSRF_EXCEPT', '')))
),
```

Route paths that should bypass CSRF protection. Add exclusions only when necessary and document the reason in your project documentation.

**Valid use cases for exclusions:**
- Public API endpoints with no session state
- Webhook endpoints from third-party services
- Endpoints with their own authentication mechanism

**Example:**
```env
CSRF_EXCEPT=/api/webhook,/stripe/callback
```

## Deployment Requirements

### Directory Permissions

Ensure the session storage directory has the correct permissions:

```bash
# Create the directory
mkdir -p storage/framework/sessions

# Set restrictive permissions
chmod 700 storage/framework/sessions

# Ensure the web server user owns the directory
chown www-data:www-data storage/framework/sessions
```

### Environment Variables

Set the following environment variables in production:

```env
SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
SESSION_COOKIE=sahil_session
```

### HTTPS Requirement

For production deployments, **HTTPS is required** when `SESSION_SECURE_COOKIE` is set to `true`. Without HTTPS, the cookie will not be sent and sessions will not work.

### Garbage Collection

Session garbage collection runs probabilistically (1% chance per request) to clean up expired sessions. This ensures:
- Expired sessions are cleaned up without impacting performance
- No separate cron job is required
- Cleanup scales with traffic

For high-traffic applications, you may want to implement a scheduled cleanup task instead of relying on probabilistic collection.

## Security Best Practices

### 1. Never Store Authentication Secrets in Plaintext

Session files are stored as JSON and should never contain:
- Plain text passwords
- API keys or tokens
- Sensitive personal information

Store references (e.g., user IDs) instead of the actual data.

### 2. Use Session ID Rotation on Privilege Escalation

When a user logs in or their privilege level changes, regenerate the session ID:

```php
session()->regenerate(true); // true = destroy old session
```

This prevents session fixation attacks where an attacker fixes a victim's session ID before login.

### 3. Configure Appropriate Session Lifetime

Shorter session lifetimes improve security but reduce user convenience. Choose a lifetime appropriate for your application's security requirements.

### 4. Enable Secure Cookies in Production

Always set `SESSION_SECURE_COOKIE=true` in production to prevent cookie theft over unencrypted connections.

### 5. Keep HttpOnly Enabled

Never disable `SESSION_HTTP_ONLY`. This prevents XSS attacks from stealing session cookies via JavaScript.

### 6. Use Appropriate SameSite Setting

- Use `'lax'` for most applications (allows navigation but prevents CSRF)
- Use `'strict'` for high-security applications (prevents all cross-site cookie sending)
- Never use `'none'` unless absolutely necessary and with HTTPS

### 7. Minimize CSRF Exclusions

Only exclude routes from CSRF protection when absolutely necessary. Document each exclusion with the reason in your project documentation.

### 8. Store Sessions Outside Web Root

Ensure the session storage path is outside the public web root to prevent direct access to session files via HTTP.

### 9. Monitor Session Storage

Regularly monitor the session storage directory for:
- Unusual file counts (potential session flooding attacks)
- Files with incorrect permissions
- Unexpected file sizes

### 10. Implement Rate Limiting

Consider implementing rate limiting on session creation to prevent session flooding attacks.

## CSRF Protection

### How It Works

CSRF protection is implemented via the `VerifyCsrfToken` middleware, which:

1. Validates CSRF tokens for unsafe HTTP methods (POST, PUT, PATCH, DELETE)
2. Allows safe methods (GET, HEAD, OPTIONS) without token validation
3. Regenerates tokens on successful validation to prevent token reuse
4. Supports token transmission via:
   - POST input field (`_token`)
   - JSON body (`_token`)
   - `X-CSRF-TOKEN` header
   - `X-XSRF-TOKEN` header (for backward compatibility)

### Including CSRF Tokens in Forms

In HTML forms, include the CSRF token as a hidden input:

```html
<form method="POST" action="/submit">
    <input type="hidden" name="_token" value="{{ csrf_token }}">
    <!-- other form fields -->
</form>
```

The template language only exposes its whitelisted helpers, so the token is
passed in as data: the controller renders the view with
`'csrf_token' => csrf_token()`.

### Including CSRF Tokens in AJAX Requests

For AJAX requests, include the token in the request headers:

```javascript
fetch('/api/endpoint', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken
    },
    body: JSON.stringify({ data: 'value' })
});
```

Or include it in the JSON body:

```javascript
fetch('/api/endpoint', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        _token: csrfToken,
        data: 'value'
    })
});
```

### Excluding Routes from CSRF Protection

Add route paths to the `csrf_except` configuration or use the middleware constructor:

```php
// In config/session.php
'csrf_except' => ['/api/webhook', '/stripe/callback']

// Or when registering middleware
$middleware = new VerifyCsrfToken($session, except: ['/api/webhook']);
```

**Only exclude routes when:**
- The endpoint is a public API with no session state
- The endpoint has its own authentication mechanism
- The endpoint is used by third-party webhooks

## Testing

Session and CSRF security tests are located in:

- `tests/Session/FileSessionDriverTest.php` - Tests for the file-based session driver
- `tests/Session/SessionTest.php` - Tests for the session manager
- `tests/Http/VerifyCsrfTokenTest.php` - Tests for CSRF verification middleware

Run the tests:

```bash
vendor/bin/phpunit tests/Session/ tests/Http/VerifyCsrfTokenTest.php
```

## Troubleshooting

### Sessions Not Persisting

1. Check that the session storage directory is writable by the web server
2. Verify that `SESSION_PATH` is correctly configured
3. Check for errors in the application logs
4. Ensure cookies are being sent (check browser developer tools)

### CSRF Token Validation Failing

1. Verify the token is being included in the request
2. Check that the token is transmitted in the correct location (POST field, header, or JSON body)
3. Ensure the session is started before generating the token
4. Verify the route is not in the `csrf_except` list

### Cookie Not Being Set

1. Verify `SESSION_SECURE_COOKIE` matches your HTTPS configuration
2. Check that the domain and path settings are correct
3. Ensure the response is not setting conflicting cookies
4. Check browser console for cookie-related errors

### Session ID Not Rotating

1. Verify that `session()->regenerate()` is being called on privilege escalation
2. Check that the session is started before calling regenerate
3. Ensure the session driver supports ID generation

## Implementation Details

### Session Driver Interface

The `SessionDriverContract` interface defines the contract for session storage drivers:

```php
interface SessionDriverContract
{
    public function read(string $id): array;
    public function write(string $id, array $data): void;
    public function destroy(string $id): void;
    public function gc(int $lifetime): void;
    public function generateId(): string;
    public function isValidId(string $id): bool;
}
```

### Session Manager

The `Session` class provides the high-level session interface with:

- Request-scoped isolation via `startFromRequest()`
- Cookie application via `applyCookieToResponse()`
- CSRF token management
- Automatic garbage collection

### Middleware

Two middleware classes are provided:

1. `StartSession` - Starts the session from the request and saves it at the end
2. `VerifyCsrfToken` - Validates CSRF tokens for unsafe HTTP methods

Both are registered as global middleware in `WebRoutesProvider`.

## Future Enhancements

Potential future enhancements to the session system:

- Redis session driver for distributed applications
- Database session driver for SQL-based storage
- Session encryption for additional security
- Multi-factor authentication session handling
- Session analytics and monitoring
- Custom session serialization formats
