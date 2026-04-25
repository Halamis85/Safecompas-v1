<?php

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
        $middleware->web(append: [
            \App\Http\Middleware\SecurityHeaders::class,
        ]);

        // Aliasy pro routing
        $middleware->alias([
            'custom.auth'      => \App\Http\Middleware\CustomAuth::class,
            'role'             => \App\Http\Middleware\RoleMiddleware::class,
            'permission'       => \App\Http\Middleware\PermissionMiddleware::class,
            'lekarnick.access' => \App\Http\Middleware\LekarnickAccessMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Pokud chcete custom error pages, nemusíte sem sahat -
        // stačí vytvořit resources/views/errors/{404,403,500}.blade.php
    })->create();
