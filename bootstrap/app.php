<?php

use App\Http\Middleware\EnsureSchemaIsReady;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'schema.ready' => EnsureSchemaIsReady::class,
            'role' => RoleMiddleware::class,
        ]);

        $middleware->web(prepend: [
            EnsureSchemaIsReady::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            '_setup/run-migrations',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
