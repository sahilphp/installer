<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Sahil\Auth\AuthManager;
use Sahil\Http\Middleware\MiddlewareContract;
use Sahil\Http\Request;
use Sahil\Http\Response;

/**
 * Send guests to the sign-in page instead of a 401 JSON body.
 *
 * The framework's `Authenticate` middleware is API-oriented; the generated
 * pages are server-rendered, so they need a redirect. Attach it to the routes
 * that require an account (the dashboard by default).
 */
final class RequireAuth implements MiddlewareContract
{
    public function __construct(private readonly AuthManager $auth)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->auth->guest()) {
            return Response::redirect('/login', 303);
        }

        return $next($request);
    }
}
