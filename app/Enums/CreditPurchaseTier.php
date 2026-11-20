<?php

namespace App\Enums;

enum CreditPurchaseTier: int
{
    case TIER_5   = 5;
    case TIER_10  = 10;
    case TIER_25  = 25;
    case TIER_50  = 50;
    case TIER_100 = 100;

    public function label(): string
    {
        return '$' . $this->value;
    }

    /**
     * Return all integer values.
     *
     * @return list<int>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
