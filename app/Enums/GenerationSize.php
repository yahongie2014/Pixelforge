<?php

namespace App\Enums;

enum GenerationSize: string
{
    case SQUARE    = '1024x1024';
    case LANDSCAPE = '1536x1024';
    case PORTRAIT  = '1024x1536';

    /**
     * Aspect ratio representation.
     */
    public function aspectRatio(): string
    {
        return match ($this) {
            self::SQUARE    => '1:1',
            self::LANDSCAPE => '3:2',
            self::PORTRAIT  => '2:3',
        };
    }

    /**
     * Human-friendly label.
     */
    public function label(): string
    {
        return match ($this) {
            self::SQUARE    => 'Square (1024×1024)',
            self::LANDSCAPE => 'Landscape (1536×1024)',
            self::PORTRAIT  => 'Portrait (1024×1536)',
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
