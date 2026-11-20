<?php

namespace App\Http\Controllers;

use App\Actions\PurchaseCreditsAction;
use App\Http\Requests\BuyCreditRequest;
use Illuminate\Http\RedirectResponse;

class CreditController extends Controller
{
    public function __construct(
        private readonly PurchaseCreditsAction $purchase,
    ) {}

    /**
     * Add credits to the authenticated user's account (simulated purchase).
     */
    public function buy(BuyCreditRequest $request): RedirectResponse
    {
        $amount = $request->amount();

        $this->purchase->execute($request->user(), $amount);

        return back()->with('success', "Successfully added \${$amount}.00 in credits to your balance!");
    }
}
