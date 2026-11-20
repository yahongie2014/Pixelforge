<?php

namespace App\Http\Requests;

use App\Enums\GenerationQuality;
use App\Enums\GenerationSize;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGenerationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'prompt'             => ['required', 'string', 'max:4000'],
            'size'               => ['required', Rule::enum(GenerationSize::class)],
            'quality'            => ['required', Rule::enum(GenerationQuality::class)],
            'reference_images'   => ['nullable', 'array', 'max:4'],
            'reference_images.*' => ['nullable', 'url'],
        ];
    }

    public function messages(): array
    {
        return [
            'prompt.required'  => 'Please enter a prompt for your image.',
            'size.in'          => 'Please choose a valid image size.',
            'quality.in'       => 'Please choose a valid quality level.',
        ];
    }
}
