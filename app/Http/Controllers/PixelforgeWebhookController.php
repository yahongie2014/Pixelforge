<?php

namespace App\Http\Controllers;

use App\Actions\HandleWebhookEventAction;
use App\Http\Requests\PixelforgeWebhookRequest;
use Illuminate\Http\Response;

class PixelforgeWebhookController extends Controller
{
    public function __construct(
        private readonly HandleWebhookEventAction $handleEvent,
    ) {}

    /**
     * Handle incoming Pixelforge webhooks.
     * Signature verification and payload structure validation are handled in PixelforgeWebhookRequest.
     */
    public function __invoke(PixelforgeWebhookRequest $request): Response
    {
        $this->handleEvent->execute($request->event(), $request->deliveryAttempt());

        return response()->noContent();
    }
}
