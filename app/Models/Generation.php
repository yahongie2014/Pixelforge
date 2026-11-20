<?php

namespace App\Models;

use App\Enums\GenerationQuality;
use App\Enums\GenerationSize;
use App\Enums\GenerationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Generation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'pixelforge_id',
        'idempotency_key',
        'status',
        'prompt',
        'size',
        'quality',
        'reference_images',
        'metadata',
        'image_url',
        'image_url_expires_at',
        'error_code',
        'error_message',
        'input_text_tokens',
        'input_image_tokens',
        'output_tokens',
        'reasoning_tokens',
        'total_tokens',
        'rates',
        'estimated_cost_provider',
        'estimated_cost_user',
        'actual_cost_provider',
        'actual_cost_user',
        'pixelforge_created_at',
        'pixelforge_started_at',
        'pixelforge_completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => GenerationStatus::class,
            'size' => GenerationSize::class,
            'quality' => GenerationQuality::class,
            'reference_images' => 'array',
            'metadata' => 'array',
            'rates' => 'array',
            'image_url_expires_at' => 'datetime',
            'pixelforge_created_at' => 'datetime',
            'pixelforge_started_at' => 'datetime',
            'pixelforge_completed_at' => 'datetime',
        ];
    }

    // Normalize money decimal fields to 9dp strings on get (SQLite strips trailing zeros)
    protected function normalizeDecimal(?string $value): string
    {
        return number_format((float) ($value ?? 0), 9, '.', '');
    }

    public function getEstimatedCostProviderAttribute(?string $value): string
    {
        return $this->normalizeDecimal($value);
    }

    public function getEstimatedCostUserAttribute(?string $value): string
    {
        return $this->normalizeDecimal($value);
    }

    public function getActualCostProviderAttribute(?string $value): string
    {
        return $this->normalizeDecimal($value);
    }

    public function getActualCostUserAttribute(?string $value): string
    {
        return $this->normalizeDecimal($value);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isTerminal(): bool
    {
        $status = $this->status instanceof GenerationStatus
            ? $this->status
            : GenerationStatus::tryFrom((string) $this->status);

        return $status?->isTerminal() ?? false;
    }

    /**
     * Determine status precedence rank to prevent out-of-order state regression.
     * queued = 1, processing = 2, terminal (succeeded/failed) = 3
     */
    public static function statusRank(string|GenerationStatus $status): int
    {
        if ($status instanceof GenerationStatus) {
            return $status->rank();
        }

        return GenerationStatus::tryFrom((string) $status)?->rank() ?? 0;
    }
}
