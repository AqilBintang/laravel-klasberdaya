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
            if (str_starts_with($path, 'admin') || str_starts_with($path, 'validator') || str_starts_with($path, 'kabupaten')) {
                return route('admin.login');
            }
            return route('kecamatan.login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // JSON hanya untuk request yang eksplisit minta JSON atau path api/*
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Pastikan exception message tidak bocor di production via JSON response
        $exceptions->respond(function (\Illuminate\Http\Response|\Symfony\Component\HttpFoundation\Response $response) {
            if (app()->environment('production') && $response instanceof \Illuminate\Http\JsonResponse) {
                $data = $response->getData(true);
                // Hapus stack trace dan detail exception dari JSON response production
                foreach (['exception', 'trace', 'file', 'line'] as $key) {
                    unset($data[$key]);
                }
                $response->setData($data);
            }
            return $response;
        });
    })->create();
