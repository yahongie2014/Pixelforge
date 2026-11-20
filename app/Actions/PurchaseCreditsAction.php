<?php

namespace App\Actions;

use App\Models\CreditTransaction;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;

class PurchaseCreditsAction
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {}

    /**
     * Simulate a credit purchase by adding the given amount to the user's balance.
     */
    public function execute(User $user, float $amount): CreditTransaction
    {
        $amountStr = number_format($amount, 9, '.', '');

        return $this->users->addBalance(
            $user,
            $amountStr,
            "Simulated credit purchase (+\${$amount}.00)",
            'top_up'
        );
    }
}
