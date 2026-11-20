<?php

namespace App\Enums;

enum GenerationQuality: string
{
    case LOW    = 'low';
    case MEDIUM = 'medium';
    case HIGH   = 'high';

    /**
     * Human-friendly label.
     */
    public function label(): string
    {
        return match ($this) {
            self::LOW    => 'Low',
            self::MEDIUM => 'Medium',
            self::HIGH   => 'High',
        };
    }

    /**
     * Secondary subtitle description for UI selectors.
     */
    public function subtitle(): string
    {
        return match ($this) {
            self::LOW    => 'Draft',
            self::MEDIUM => 'Standard',
            self::HIGH   => 'Detailed',
        };
    }

    /**
     * Reasoning token range min/max.
     *
     * @return array{min: int, max: int}
     */
    public function reasoningRange(): array
    {
        return match ($this) {
            self::LOW    => ['min' => 0, 'max' => 500],
            self::MEDIUM => ['min' => 200, 'max' => 2000],
            self::HIGH   => ['min' => 1000, 'max' => 8000],
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
