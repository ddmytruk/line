<?php

declare(strict_types=1);

namespace Payroll\Domain\Exception;

use Payroll\Domain\Currency;

final class CurrencyMismatch extends PayrollException
{
    public static function between(Currency $expected, Currency $actual): self
    {
        return new self(\sprintf('Expected an amount in %s, got %s.', $expected->value, $actual->value));
    }
}
