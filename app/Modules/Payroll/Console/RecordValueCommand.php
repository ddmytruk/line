<?php

declare(strict_types=1);

namespace App\Modules\Payroll\Console;

use Common\Application\Command\CommandBus;
use Illuminate\Console\Command;
use Payroll\Application\Command\RecordCalculatedValue;

/** Records a value the payroll calculation produced: creates the line or recalculates it. */
final class RecordValueCommand extends Command
{
    protected $signature = 'payroll:record-value
        {line : id of the line (UUID)}
        {amount : exact decimal amount, for example 1050.00}
        {currency : currency code, for example USD}
        {--command-id= : id of this request; send the same id to repeat it safely (default: a new UUID)}';

    protected $description = 'Record a calculated value of an earning line';

    public function handle(CommandBus $bus): int
    {
        $commandId = CommandOptions::commandId($this->option('command-id'));

        return Refusals::reported($this, function () use ($bus, $commandId): int {
            $bus->dispatch(new RecordCalculatedValue(
                $commandId,
                CommandOptions::text($this->argument('line')),
                CommandOptions::text($this->argument('amount')),
                CommandOptions::text($this->argument('currency')),
            ));
            $this->info(\sprintf('Recorded (command id %s).', $commandId));

            return self::SUCCESS;
        });
    }
}
