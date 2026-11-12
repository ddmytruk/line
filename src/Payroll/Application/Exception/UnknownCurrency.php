<?php

declare(strict_types=1);

namespace Payroll\Application\Exception;

/** A command named a currency that lines cannot be paid in. The message does not repeat the input. */
final class UnknownCurrency extends \InvalidArgumentException
{
    public static function create(): self
    {
        return new self('The currency is not supported.');
    }
}
