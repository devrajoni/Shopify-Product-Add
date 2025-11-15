<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Tymon\JWTAuth\Http\Middleware\Authenticate as JWTAuthenticate;
use Tymon\JWTAuth\Http\Middleware\RefreshToken as JWTRefreshToken;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        /**
         * -----------------------------------------------------------------
         * Global & Route Middleware Registration
         * -----------------------------------------------------------------
         */
        $middleware->alias([
            'jwt.auth' => JWTAuthenticate::class,
            'jwt.refresh' => JWTRefreshToken::class,
        ]);

        // You can register additional middleware here if required
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        /**
         * -----------------------------------------------------------------
         * Global Exception Handler
         * -----------------------------------------------------------------
         * Customize how exceptions are reported or rendered here.
         */
        $exceptions->report(function (Throwable $e) {
            // Example: handle or log specific exceptions
            // if ($e instanceof \Illuminate\Auth\AuthenticationException) {
            //     Log::warning('Authentication failed: ' . $e->getMessage());
            // }
        });
    })
    ->create();
