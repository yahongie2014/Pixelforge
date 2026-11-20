<?php

namespace App\Repositories\Contracts;

use App\Models\CreditTransaction;
use App\Models\Generation;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface UserRepositoryInterface
{
    public function findById(int $id): ?User;

    /**
     * Return all users, each loaded with generation counts.
     * Used for the admin report user breakdown table.
     */
    public function getAllWithGenerationCounts(): Collection;

    /**
     * Atomically deduct credits from a user's balance and record the transaction.
     */
    public function deductBalance(
        User   $user,
        string $amount,
        Generation $generation,
        string $description
    ): CreditTransaction;

    /**
     * Atomically add credits to a user's balance and record the transaction.
     */
    public function addBalance(
        User   $user,
        string $amount,
        string $description,
        string $type = 'top_up'
    ): CreditTransaction;
}
