<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Telescope::night();

        $this->hideSensitiveRequestDetails();

        $isLocal = $this->app->environment('local');

        Telescope::filter(function (IncomingEntry $entry) use ($isLocal) {
            return $isLocal ||
                   $entry->isReportableException() ||
                   $entry->isFailedRequest() ||
                   $entry->isFailedJob() ||
                   $entry->isScheduledTask() ||
                   $entry->hasMonitoredTag() ||
                   in_array($entry->type, ['redis', 'request', 'log', 'query']);
        });

        Telescope::tag(function (IncomingEntry $entry) {
            $tags = [];

            if ($entry->type === 'request') {
                $status = $entry->content['response_status'] ?? null;
                if ($status) {
                    $tags[] = "status:{$status}";
                }
                $uri = $entry->content['uri'] ?? '';
                if (str_contains($uri, 'generations')) {
                    $tags[] = 'domain:generations';
                } elseif (str_contains($uri, 'webhooks')) {
                    $tags[] = 'domain:webhooks';
                } elseif (str_contains($uri, 'admin')) {
                    $tags[] = 'domain:admin';
                }
            }

            if (isset($entry->content['user']['id'])) {
                $tags[] = 'user:' . $entry->content['user']['id'];
            }

            return $tags;
        });
    }

    /**
     * Prevent sensitive request details from being logged by Telescope.
     */
    protected function hideSensitiveRequestDetails(): void
    {
        if ($this->app->environment('local')) {
            return;
        }

        Telescope::hideRequestParameters(['_token']);

        Telescope::hideRequestHeaders([
            'cookie',
            'set-cookie',
            'x-csrf-token',
            'x-xsrf-token',
        ]);
    }

    /**
     * Register the Telescope gate.
     *
     * This gate determines who can access Telescope in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewTelescope', function (User $user) {
            return (bool) $user->is_admin || in_array($user->email, [
                'admin@promptedit.com',
            ]);
        });
    }
}
