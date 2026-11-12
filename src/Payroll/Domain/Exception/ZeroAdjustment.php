<?php

declare(strict_types=1);

namespace Payroll\Domain\Exception;

final class ZeroAdjustment extends PayrollException
{
    public static function notAllowed(): self
    {
        return new self('A manual adjustment must be a positive or negative amount, not zero.');
    }
}
