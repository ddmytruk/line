<?php

declare(strict_types=1);

namespace Payroll\Application\Exception;

use Payroll\Domain\EarningLineId;

/** The stored history of a line breaks the rules of the domain, so the line cannot be rebuilt safely. */
final class CorruptedEarningLineEvents extends \RuntimeException
{
    public static function of(EarningLineId $id, \Throwable $reason): self
    {
        return new self(\sprintf('The history of earning line %s is corrupted: %s', $id->value, $reason->getMessage()), 0, $reason);
    }
}
