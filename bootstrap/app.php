<?php
// bootstrap/app.php

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
        // Registrace custom middleware
        $middleware->alias([
            'custom.auth' => \App\Http\Middleware\CustomAuth::class,
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'permission' => \App\Http\Middleware\PermissionMiddleware::class,
            'lekarnick.access' => \App\Http\Middleware\LekarnickAccessMiddleware::class,
        ]);

        // Globální middleware pro web routes
        $middleware->web(append: [
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
