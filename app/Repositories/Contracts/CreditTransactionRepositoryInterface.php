<?php

namespace App\Repositories\Contracts;

use App\Models\CreditTransaction;

interface CreditTransactionRepositoryInterface
{
    public function create(array $data): CreditTransaction;

    public function existsForGeneration(int $generationId): bool;
}
