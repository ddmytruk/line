<?php

declare(strict_types=1);

namespace Payroll\Application\Command;

use Common\Application\Clock;
use Common\Application\Command\Command;

/** The handlers of the Payroll commands, ready to be given to the command bus. */
final class PayrollCommandHandlers
{
    /** @return array<class-string<Command>, callable(Command): void> */
    public static function create(EarningLines $lines, Clock $clock): array
    {
        $record = new RecordCalculatedValueHandler($lines, $clock);
        $adjust = new AddManualAdjustmentHandler($lines, $clock);

        return [
            RecordCalculatedValue::class => static function (Command $command) use ($record): void {
                \assert($command instanceof RecordCalculatedValue);
                $record($command);
            },
            AddManualAdjustment::class => static function (Command $command) use ($adjust): void {
                \assert($command instanceof AddManualAdjustment);
                $adjust($command);
            },
        ];
    }
}
