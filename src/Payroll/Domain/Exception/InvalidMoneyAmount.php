<?php

declare(strict_types=1);

namespace Payroll\Domain\Exception;

final class InvalidMoneyAmount extends PayrollException
{
    public static function notExact(): self
    {
        return new self('An amount must be a decimal number with at most two decimal places, for example -45.55.');
    }

    public static function outOfRange(): self
    {
        return new self('The amount is out of the supported range.');
    }
}
