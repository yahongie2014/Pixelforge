<?php

namespace App\Http\Requests;

use App\Enums\GenerationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates the status filter query param on the generation history page.
 */
class GenerationIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', Rule::enum(GenerationStatus::class)],
        ];
    }
}
