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
        
        // Tell Laravel to trust all proxies (Fixes HTTPS mixed content for Cloudflare/AWS)
        $middleware->trustProxies(at: '*');

        // 1. From the Inertia snippet: Appending middleware to the 'web' group
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            \App\Http\Middleware\CheckUserStatus::class, // <-- Added CheckUserStatus middleware here
        ]);

        // 2. Registering the middleware aliases
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'prevent-back-history' => \App\Http\Middleware\PreventBackHistory::class, // <-- Added this
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();