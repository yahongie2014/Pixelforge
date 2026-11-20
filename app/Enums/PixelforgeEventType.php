<?php

namespace App\Enums;

enum PixelforgeEventType: string
{
    case PING                  = 'ping';
    case GENERATION_PROCESSING = 'generation.processing';
    case GENERATION_SUCCEEDED  = 'generation.succeeded';
    case GENERATION_FAILED     = 'generation.failed';

    /**
     * Map webhook event type to generation status, if applicable.
     */
    public function targetStatus(): ?GenerationStatus
    {
        return match ($this) {
            self::GENERATION_PROCESSING => GenerationStatus::PROCESSING,
            self::GENERATION_SUCCEEDED  => GenerationStatus::SUCCEEDED,
            self::GENERATION_FAILED     => GenerationStatus::FAILED,
            default                     => null,
        };
    }

    /**
     * Return all string values.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
