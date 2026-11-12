<?php

declare(strict_types=1);

namespace App\Modules\Payroll\Console;

use Common\Application\Exception\CommandIdReused;
use Common\Application\Exception\ConcurrencyConflict;
use Illuminate\Console\Command;
use Payroll\Application\Exception\EarningLineNotFound;
use Payroll\Application\Exception\UnknownCurrency;
use Payroll\Domain\Exception\PayrollException;

/** Failures a person at the command line can act on are printed as a message; anything else is a fault and is thrown. */
final class Refusals
{
    /** @param \Closure(): int $work */
    public static function reported(Command $command, \Closure $work): int
    {
        try {
            return $work();
        } catch (PayrollException|UnknownCurrency|EarningLineNotFound|ConcurrencyConflict|CommandIdReused $refusal) {
            $command->error($refusal->getMessage());

            return Command::FAILURE;
        }
    }
}
