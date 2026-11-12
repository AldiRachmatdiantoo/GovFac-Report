<?php

use App\Modules\Auth\Http\Middleware\AuthenticateFromCookie;
use App\Modules\Auth\Http\Middleware\VerifyXsrfHeader;
use App\Modules\Shared\Http\Middleware\EnsureRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Urutan penting: cookie dibaca dulu, baru guard `auth:api` berjalan.
        $middleware->api(prepend: [
            AuthenticateFromCookie::class,
            VerifyXsrfHeader::class,
        ]);

        $middleware->alias([
            'role' => EnsureRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
