<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'hospi.role' => \App\Http\Middleware\EnsureRole::class,
            'hospi.resort' => \App\Http\Middleware\EnsureResortTenant::class,
        ]);

        /*
         * Where an unauthenticated caller is sent.
         *
         * Laravel's default is `fn () => route('login')`, and this application
         * has no route named `login` — Filament registers
         * `filament.admin.auth.login`. That default is evaluated INSIDE
         * Authenticate::unauthenticated(), before the exception handler sees
         * anything, so it threw RouteNotFoundException and every tokenless
         * request to a protected /api route that did not send
         * `Accept: application/json` came back 500 instead of 401 — an HTML
         * stack trace to an anonymous caller whenever APP_DEBUG is on.
         *
         * Returning null for API callers leaves the AuthenticationException
         * without a redirect, and the shouldRenderJsonWhen() rule below turns it
         * into a JSON 401.
         */
        $middleware->redirectGuestsTo(function (\Illuminate\Http\Request $request): ?string {
            if ($request->is('api/*') || $request->expectsJson()) {
                return null;
            }

            return \Illuminate\Support\Facades\Route::has('filament.admin.auth.login')
                ? route('filament.admin.auth.login')
                : '/admin/login';
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        /*
         * Every /api/* failure is rendered as JSON, whatever the caller's
         * Accept header says.
         *
         * Without this, an unauthenticated request to any protected /api route
         * that does NOT send `Accept: application/json` produced a 500, not a
         * 401: Laravel's handler falls back to redirect()->guest(route('login'))
         * and this application has no route named `login` (Filament registers
         * `filament.admin.auth.login`), so the AuthenticationException became a
         * RouteNotFoundException. With APP_DEBUG on that 500 is a full HTML
         * stack trace served to an anonymous caller; with it off it is still the
         * wrong status code and it hides real 401s in the error logs.
         *
         * Scoped to api/* so the website's own error pages are untouched.
         */
        $exceptions->shouldRenderJsonWhen(
            fn (\Illuminate\Http\Request $request, \Throwable $e): bool => $request->is('api/*')
                || $request->expectsJson(),
        );
    })->create();
