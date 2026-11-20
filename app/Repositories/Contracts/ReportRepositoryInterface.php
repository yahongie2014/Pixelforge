<?php

namespace App\Repositories\Contracts;

use Carbon\Carbon;

interface ReportRepositoryInterface
{
    /**
     * Get aggregated daily generation statistics grouped by completion date in UTC.
     *
     * @return array<string, array{
     *     date: string,
     *     generations: int,
     *     input_text_tokens: int,
     *     input_image_tokens: int,
     *     output_tokens: int,
     *     reasoning_tokens: int,
     *     provider_cost: string,
     *     credit_spend: string,
     *     profit: string
     * }>
     */
    public function getDailyGenerationStats(Carbon $startDate, Carbon $endDate): array;

    /**
     * Get aggregated per-user activity and spend metrics.
     *
     * @return array<int, array{
     *     id: int,
     *     name: string,
     *     email: string,
     *     is_admin: bool,
     *     balance: string,
     *     total_generations: int,
     *     succeeded_generations: int,
     *     failed_generations: int,
     *     credit_spend: string,
     *     provider_cost: string,
     *     profit: string
     * }>
     */
    public function getUserSpendReport(): array;
}
