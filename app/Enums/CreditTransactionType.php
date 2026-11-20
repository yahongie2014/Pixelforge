<?php

namespace App\Enums;

enum CreditTransactionType: string
{
    case TOP_UP            = 'top_up';
    case GENERATION_CHARGE = 'generation_charge';
    case REFUND            = 'refund';
    case ADMIN_ADJUSTMENT  = 'admin_adjustment';

    public function label(): string
    {
        return match ($this) {
            self::TOP_UP            => 'Credit Purchase',
            self::GENERATION_CHARGE => 'Generation Charge',
            self::REFUND            => 'Refund',
            self::ADMIN_ADJUSTMENT  => 'Admin Adjustment',
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
