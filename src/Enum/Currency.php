<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Devises supportées. Les cas reflètent les devises présentes dans data/*.
 * Toute devise hors de cette liste lèvera une \ValueError via Currency::from().
 */
enum Currency: string
{
    case EUR = 'EUR';
    case USD = 'USD';
    case GBP = 'GBP';
    case JPY = 'JPY';
}
