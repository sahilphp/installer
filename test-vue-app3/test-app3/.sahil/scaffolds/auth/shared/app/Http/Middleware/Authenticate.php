<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Sahil\Auth\AuthManager;
use Sahil\Http\Middleware\MiddlewareContract;
use Sahil\Http\Request;
use Sahil\Http\Response;

/**
 * Redirect guests to the sign-in page instead of returning a 401 JSON body.
 *
 * Attach it to routes that require an account; the generated dashboard uses
 * it by default.
 */
final class Authenticate implements MiddlewareContract
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
