<?php

declare(strict_types=1);

namespace App\Core\Money;

use Brick\Math\RoundingMode;
use Brick\Money\Money as BrickMoney;

final class Money
{
    public static function of(string|int $amount, string $currency = 'DOP'): BrickMoney
    {
        return BrickMoney::of($amount, $currency, null, RoundingMode::HALF_UP);
    }
}
