<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Sahil\Auth\AuthManager;
use Sahil\Http\Middleware\MiddlewareContract;
use Sahil\Http\Request;
use Sahil\Http\Response;

/**
 * Keep signed-in users away from guest pages (login, register, password
 * reset), sending them to the dashboard instead.
 */
final class RedirectIfAuthenticated implements MiddlewareContract
{
    public function __construct(private readonly AuthManager $auth)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->auth->check()) {
            return Response::redirect('/dashboard', 303);
        }

        return $next($request);
    }
}
