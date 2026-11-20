<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // ── Redis Rate Limiters for Requests ──────────────────────
        RateLimiter::for('generations', function (Request $request) {
            $key = $request->user()?->id ?: $request->ip();

            return Limit::perMinute(30)->by($key)->response(function () {
                Log::warning('Redis RateLimiter: Generation limit exceeded', [
                    'user_id' => auth()->id(),
                    'ip'      => request()->ip(),
                ]);

                return response()->json([
                    'message' => 'Too many generation requests. Please wait a moment before trying again.',
                ], 429);
            });
        });

        RateLimiter::for('estimates', function (Request $request) {
            $key = $request->user()?->id ?: $request->ip();

            return Limit::perMinute(120)->by($key);
        });

        RateLimiter::for('webhooks', function (Request $request) {
            return Limit::perMinute(300)->by($request->ip())->response(function () {
                Log::warning('Redis RateLimiter: Webhook rate limit exceeded', [
                    'ip' => request()->ip(),
                ]);

                return response()->json([
                    'error' => 'Rate limit exceeded',
                ], 429);
            });
        });

        // ── Failure Logging for Queue Jobs ────────────────────────
        Queue::failing(function (JobFailed $event) {
            Log::error('Queue Job Failed: ' . $event->job->resolveName(), [
                'connection' => $event->connectionName,
                'queue'      => $event->job->getQueue(),
                'exception'  => $event->exception->getMessage(),
                'payload'    => $event->job->getRawBody(),
                'trace'      => collect($event->exception->getTrace())->take(5)->toArray(),
            ]);
        });
    }
}
