<?php

declare(strict_types=1);

namespace Payroll\Domain;

/** Currencies a line can be paid in. All of them have two decimal places, which is what Money relies on. */
enum Currency: string
{
    case USD = 'USD';
    case EUR = 'EUR';
    case GBP = 'GBP';
    case UAH = 'UAH';
}
