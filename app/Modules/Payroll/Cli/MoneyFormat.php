<?php

declare(strict_types=1);

namespace App\Modules\Payroll\Cli;

use Payroll\Domain\Currency;
use Payroll\Domain\Money;

/** Money for people: "$1,050.00", "+$100.10", "-$45.55". */
final class MoneyFormat
{
    public static function amount(Money $money): string
    {
        $absolute = abs($money->minorUnits);

        return \sprintf(
            '%s%s%s.%02d',
            $money->minorUnits < 0 ? '-' : '',
            self::symbol($money->currency),
            number_format(intdiv($absolute, 100), 0, '.', ','),
            $absolute % 100,
        );
    }

    /** Adjustments always show their sign. */
    public static function signed(Money $money): string
    {
        return ($money->minorUnits < 0 ? '' : '+') . self::amount($money);
    }

    private static function symbol(Currency $currency): string
    {
        return match ($currency) {
            Currency::USD => '$',
            Currency::EUR => '€',
            Currency::GBP => '£',
            Currency::UAH => '₴',
        };
    }
}
