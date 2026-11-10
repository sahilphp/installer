<?php

declare(strict_types=1);

/** SahilPHP public entry point (HTTP). */

use Sahil\Http\Emitter\DefaultResponseEmitter;

/**
 * Let the PHP built-in server send existing public files directly instead of
 * running them through the router. The realpath check keeps the lookup inside
 * public/; a web server in production should serve these files itself.
 */
if (PHP_SAPI === 'cli-server') {
    $publicRoot = realpath(__DIR__);
    $requestPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);

    if (is_string($requestPath) && $publicRoot !== false && $requestPath !== '/') {
        $target = realpath(__DIR__.$requestPath);

        if ($target !== false
            && str_starts_with($target, $publicRoot.DIRECTORY_SEPARATOR)
            && is_file($target)
        ) {
            return false;
        }
    }
}
use Sahil\Http\Kernel;
use Sahil\Http\Request;
use Sahil\Http\Response;

/** Sequenced boot-stream: bootstrap errors cannot leak anything. */
try {
    require_once __DIR__.'/../bootstrap/app.php';

    $application = sahil_bootstrap();
    $request = Request::fromGlobals();

    /** @var Kernel $kernel */
    $kernel = $application->container()->make(Kernel::class);

    $response = $kernel->handle($request);

} catch (Throwable $bootFailure) {
    $response = Response::json(['error' => 'server_error'], 500);
    $request = null;
}

(new DefaultResponseEmitter())->emit($response, $request);