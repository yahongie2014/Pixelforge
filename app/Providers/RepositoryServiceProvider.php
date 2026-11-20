<?php

namespace App\Providers;

use App\Repositories\Contracts\CreditTransactionRepositoryInterface;
use App\Repositories\Contracts\GenerationRepositoryInterface;
use App\Repositories\Contracts\ReportRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\WebhookEventRepositoryInterface;
use App\Repositories\Eloquent\EloquentCreditTransactionRepository;
use App\Repositories\Eloquent\EloquentGenerationRepository;
use App\Repositories\Eloquent\EloquentReportRepository;
use App\Repositories\Eloquent\EloquentUserRepository;
use App\Repositories\Eloquent\EloquentWebhookEventRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register repository interface → Eloquent implementation bindings.
     */
    public function register(): void
    {
        $this->app->bind(GenerationRepositoryInterface::class, EloquentGenerationRepository::class);
        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);
        $this->app->bind(WebhookEventRepositoryInterface::class, EloquentWebhookEventRepository::class);
        $this->app->bind(CreditTransactionRepositoryInterface::class, EloquentCreditTransactionRepository::class);
        $this->app->bind(ReportRepositoryInterface::class, EloquentReportRepository::class);
    }
}
