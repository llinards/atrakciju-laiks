<?php

use App\Http\Middleware\ApplySiteSettings;
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
        $middleware->web(append: [
            ApplySiteSettings::class,
        ]);

        // The cookie-consent package writes and reads its preference cookie
        // unencrypted, so EncryptCookies must leave it untouched. Must match
        // cookieconsent.cookie.name (config is not yet available here).
        $middleware->encryptCookies(except: [
            'atrakciju_laiks_cookie_consent',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
