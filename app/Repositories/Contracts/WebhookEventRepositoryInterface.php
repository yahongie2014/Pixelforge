<?php

namespace App\Repositories\Contracts;

use App\Models\PixelforgeWebhookEvent;

interface WebhookEventRepositoryInterface
{
    public function existsByEventId(string $eventId): bool;

    public function findByEventId(string $eventId): ?PixelforgeWebhookEvent;

    public function create(array $data): PixelforgeWebhookEvent;
}
