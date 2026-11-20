<?php

namespace App\Repositories\Contracts;

use App\Models\Generation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface GenerationRepositoryInterface
{
    public function create(array $data): Generation;

    public function update(Generation $generation, array $data): Generation;

    public function findById(int $id): ?Generation;

    public function findByPixelforgeId(string $pixelforgeId): ?Generation;

    public function findByIdempotencyKey(string $key): ?Generation;

    /**
     * Paginate a user's generations, optionally filtered by status.
     */
    public function paginateForUser(
        User $user,
        int $perPage = 12,
        ?string $status = null
    ): LengthAwarePaginator;

    /**
     * Return aggregate stats for a given user.
     *
     * @return array{total_generations: int, succeeded_count: int, total_spent: string, balance: string}
     */
    public function getStatsForUser(User $user): array;

    /**
     * Return all succeeded generations completed within a date/time range
     * (Pixelforge bills by UTC completion date).
     */
    public function getCompletedBetween(Carbon $from, Carbon $to): Collection;

    /**
     * Per-user spend summary: credit_spend, provider_cost, profit — all string decimals.
     * Returns an array keyed by user_id.
     *
     * @return array<int, array{credit_spend: string, provider_cost: string, profit: string}>
     */
    public function getSpendByUser(): array;
}
