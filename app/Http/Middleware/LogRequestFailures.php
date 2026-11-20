<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogRequestFailures
{
    /**
     * Intercept outgoing responses and log client (4xx) and server (5xx) failures.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $status = $response->getStatusCode();
        if ($status >= 400) {
            $context = [
                'status'     => $status,
                'method'     => $request->method(),
                'path'       => $request->path(),
                'url'        => $request->fullUrl(),
                'ip'         => $request->ip(),
                'user_id'    => $request->user()?->id,
                'user_email' => $request->user()?->email,
            ];

            // Exclude sensitive credentials from failure context logs
            $context['input'] = $request->except([
                'password',
                'password_confirmation',
                '_token',
                'current_password',
            ]);

            if ($status >= 500) {
                Log::error("HTTP Server Error [{$status}] on {$request->method()} {$request->path()}", $context);
            } elseif ($status === 429) {
                Log::warning("Rate Limit Exceeded [429] on {$request->method()} {$request->path()}", $context);
            } else {
                Log::notice("HTTP Client Error [{$status}] on {$request->method()} {$request->path()}", $context);
            }
        }

        return $response;
    }
}
