<?php

namespace App\Repositories\Eloquent;

use App\Models\CreditTransaction;
use App\Models\Generation;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class EloquentUserRepository implements UserRepositoryInterface
{
    public function findById(int $id): ?User
    {
        return User::find($id);
    }

    public function getAllWithGenerationCounts(): Collection
    {
        return User::withCount([
            'generations',
            'generations as succeeded_generations_count' => fn ($q) => $q->where('status', 'succeeded'),
            'generations as failed_generations_count'    => fn ($q) => $q->where('status', 'failed'),
        ])->get();
    }

    public function deductBalance(User $user, string $amount, Generation $generation, string $description): CreditTransaction
    {
        return DB::transaction(function () use ($user, $amount, $generation, $description) {
            $locked = User::where('id', $user->id)->lockForUpdate()->first();
            $amountStr = number_format((float) $amount, 9, '.', '');
            $newBalance = bcsub($locked->balance, $amountStr, 9);

            $locked->balance = $newBalance;
            $locked->save();

            // Keep the in-memory instance in sync
            $user->balance = $newBalance;

            return CreditTransaction::create([
                'user_id'       => $locked->id,
                'generation_id' => $generation->id,
                'type'          => 'generation_charge',
                'amount'        => '-' . $amountStr,
                'balance_after' => $newBalance,
                'description'   => $description ?: "Image generation #{$generation->id}",
            ]);
        });
    }

    public function addBalance(User $user, string $amount, string $description, string $type = 'top_up'): CreditTransaction
    {
        return DB::transaction(function () use ($user, $amount, $description, $type) {
            $locked = User::where('id', $user->id)->lockForUpdate()->first();
            $amountStr = number_format((float) $amount, 9, '.', '');
            $newBalance = bcadd($locked->balance, $amountStr, 9);

            $locked->balance = $newBalance;
            $locked->save();

            $user->balance = $newBalance;

            return CreditTransaction::create([
                'user_id'       => $locked->id,
                'type'          => $type,
                'amount'        => $amountStr,
                'balance_after' => $newBalance,
                'description'   => $description,
            ]);
        });
    }
}
