<?php

namespace App\Enums;

enum GenerationStatus: string
{
    case QUEUED     = 'queued';
    case PROCESSING = 'processing';
    case SUCCEEDED  = 'succeeded';
    case FAILED     = 'failed';

    /**
     * Get human-friendly label.
     */
    public function label(): string
    {
        return match ($this) {
            self::QUEUED     => 'Queued',
            self::PROCESSING => 'Processing',
            self::SUCCEEDED  => 'Succeeded',
            self::FAILED     => 'Failed',
        };
    }

    /**
     * Status precedence rank to prevent out-of-order state regression.
     * queued = 1, processing = 2, terminal (succeeded/failed) = 3
     */
    public function rank(): int
    {
        return match ($this) {
            self::QUEUED     => 1,
            self::PROCESSING => 2,
            self::SUCCEEDED, self::FAILED => 3,
        };
    }

    /**
     * Whether this status represents a final terminal state.
     */
    public function isTerminal(): bool
    {
        return in_array($this, [self::SUCCEEDED, self::FAILED], true);
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
