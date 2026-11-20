<?php

namespace App\Http\Requests;

use App\Enums\GenerationQuality;
use App\Enums\GenerationSize;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EstimateGenerationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'prompt'             => ['nullable', 'string', 'max:4000'],
            'size'               => ['nullable', Rule::enum(GenerationSize::class)],
            'quality'            => ['nullable', Rule::enum(GenerationQuality::class)],
            'reference_images'   => ['nullable', 'array', 'max:4'],
            'reference_images.*' => ['nullable', 'url'],
        ];
    }
}
