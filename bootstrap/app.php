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
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            \App\Http\Middleware\LogRequestFailures::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'webhooks/*',
            'webhooks/pixelforge',
        ]);

        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->report(function (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Application Exception: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'file'      => $e->getFile() . ':' . $e->getLine(),
                'url'       => request()->fullUrl(),
                'method'    => request()->method(),
                'ip'        => request()->ip(),
                'user_id'   => auth()->id(),
                'trace'     => collect($e->getTrace())->take(5)->toArray(),
            ]);
        });
    })->create();
