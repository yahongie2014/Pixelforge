<?php

namespace App\Repositories\Eloquent;

use App\Models\PixelforgeWebhookEvent;
use App\Repositories\Contracts\WebhookEventRepositoryInterface;

class EloquentWebhookEventRepository implements WebhookEventRepositoryInterface
{
    public function existsByEventId(string $eventId): bool
    {
        return PixelforgeWebhookEvent::where('event_id', $eventId)->exists();
    }

    public function findByEventId(string $eventId): ?PixelforgeWebhookEvent
    {
        return PixelforgeWebhookEvent::where('event_id', $eventId)->first();
    }

    public function create(array $data): PixelforgeWebhookEvent
    {
        return PixelforgeWebhookEvent::create($data);
    }
}
