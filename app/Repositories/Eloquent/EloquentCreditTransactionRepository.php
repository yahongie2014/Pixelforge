<?php

namespace App\Repositories\Eloquent;

use App\Models\CreditTransaction;
use App\Repositories\Contracts\CreditTransactionRepositoryInterface;

class EloquentCreditTransactionRepository implements CreditTransactionRepositoryInterface
{
    public function create(array $data): CreditTransaction
    {
        return CreditTransaction::create($data);
    }

    public function existsForGeneration(int $generationId): bool
    {
        return CreditTransaction::where('generation_id', $generationId)->exists();
    }
}
