<?php

namespace App\Enums;

enum RentalZone: string
{
    case City = 'city';
    case Suburb = 'suburb';
    case LongDistance = 'long_distance';
    case VeryLongDistance = 'very_long_distance';

    public function label(): string
    {
        return match ($this) {
            self::City => 'Ville',
            self::Suburb => 'Périphérie',
            self::LongDistance => 'Longue distance',
            self::VeryLongDistance => 'Très longue distance',
        };
    }
}
