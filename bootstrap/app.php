<?php

use App\Http\Middleware\FrameHeadersMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function ($middleware) {
        return [
            LocaleSessionRedirect::class,
            VerifyCsrfToken::class,
            FrameHeadersMiddleware::class,
            'throttle:global',
        ];
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
