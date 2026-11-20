<?php

namespace App\Http\Requests;

use App\Enums\CreditPurchaseTier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates the credit purchase amount against CreditPurchaseTier cases.
 */
class BuyCreditRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', Rule::enum(CreditPurchaseTier::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Please select a credit amount.',
            'amount.in'       => 'Invalid credit amount. Choose from $5, $10, $25, $50, or $100.',
        ];
    }

    /**
     * Return amount as a float after validation.
     */
    public function amount(): float
    {
        return (float) $this->validated('amount');
    }
}
