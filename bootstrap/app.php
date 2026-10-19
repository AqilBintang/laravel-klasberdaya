<?php

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
        $middleware->alias([
            'role'           => \App\Http\Middleware\RoleMiddleware::class,
            'guest_redirect' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        ]);

        // Override redirect tujuan middleware 'auth' bawaan Laravel.
        // Tanpa ini Laravel akan redirect ke route 'login' yang tidak terdefinisi.
        // Kita arahkan ke admin.login untuk path admin/validator,
        // atau kecamatan.login untuk path lainnya.
        $middleware->redirectGuestsTo(function (\Illuminate\Http\Request $request): string {
            $path = $request->path();
            if (str_starts_with($path, 'admin') || str_starts_with($path, 'validator')) {
                return route('admin.login');
            }
            return route('kecamatan.login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
