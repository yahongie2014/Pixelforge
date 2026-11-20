<?php

namespace App\Http\Requests;

use App\Services\PixelforgeService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

/**
 * Validates an incoming Pixelforge webhook request.
 *
 * authorize() performs HMAC-SHA256 signature verification so the
 * controller stays free of authentication logic.
 */
class PixelforgeWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        $pixelforge = app(PixelforgeService::class);

        $valid = $pixelforge->verifyWebhookSignature(
            $this->getContent(),
            $this->header('Pixelforge-Signature')
        );

        if (! $valid) {
            Log::warning('Pixelforge webhook: signature verification failed.', [
                'ip'        => $this->ip(),
                'signature' => $this->header('Pixelforge-Signature'),
            ]);
        }

        return $valid;
    }

    /**
     * Return 400 (not 403) when signature is invalid — matches Pixelforge expectations.
     */
    protected function failedAuthorization(): never
    {
        abort(400, 'Invalid Pixelforge webhook signature.');
    }

    public function rules(): array
    {
        return [
            'id'   => ['required', 'string'],
            'type' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required'   => 'Webhook event ID is missing.',
            'type.required' => 'Webhook event type is missing.',
        ];
    }

    /**
     * Override to validate JSON body, not form fields.
     * The JSON is decoded and merged into the request data.
     */
    protected function prepareForValidation(): void
    {
        $json = json_decode($this->getContent(), true);

        if (is_array($json)) {
            $this->merge($json);
        }
    }

    /**
     * Return 400 on validation failure (not 422) — standard for webhook endpoints.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): never
    {
        abort(400, 'Invalid event payload: ' . implode(', ', $validator->errors()->all()));
    }

    /**
     * Convenience accessor for the delivery attempt header.
     */
    public function deliveryAttempt(): int
    {
        return (int) $this->header('Pixelforge-Delivery-Attempt', 1);
    }

    /**
     * Return the decoded event payload (always an array after prepareForValidation).
     */
    public function event(): array
    {
        return json_decode($this->getContent(), true) ?? [];
    }
}
