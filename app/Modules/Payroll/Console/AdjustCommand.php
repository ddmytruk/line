<?php

declare(strict_types=1);

namespace App\Modules\Payroll\Console;

use Common\Application\Command\CommandBus;
use Illuminate\Console\Command;
use Payroll\Application\Command\AddManualAdjustment;

/** Adds a manual adjustment on behalf of a specialist, refused if the line changed since the version they saw. */
final class AdjustCommand extends Command
{
    protected $signature = 'payroll:adjust
        {line : id of the line (UUID)}
        {amount : signed exact decimal amount; write -- before the arguments when it is negative}
        {currency : currency code, for example USD}
        {comment : why the line is adjusted}
        {--expected-version= : version of the line the specialist saw (payroll:history shows it)}
        {--specialist= : id of the specialist}
        {--command-id= : id of this request; send the same id to repeat it safely (default: a new UUID)}';

    protected $description = 'Add a manual adjustment to an earning line';

    public function handle(CommandBus $bus): int
    {
        $version = $this->option('expected-version');
        $specialist = CommandOptions::text($this->option('specialist'));

        if (!\is_string($version) || preg_match('/\A\d+\z/', $version) !== 1 || $specialist === '') {
            $this->error('Both --expected-version (a whole number) and --specialist are required.');

            return self::INVALID;
        }

        $commandId = CommandOptions::commandId($this->option('command-id'));

        return Refusals::reported($this, function () use ($bus, $commandId, $version, $specialist): int {
            $bus->dispatch(new AddManualAdjustment(
                $commandId,
                CommandOptions::text($this->argument('line')),
                (int) $version,
                CommandOptions::text($this->argument('amount')),
                CommandOptions::text($this->argument('currency')),
                CommandOptions::text($this->argument('comment')),
                $specialist,
            ));
            $this->info(\sprintf('Adjusted (command id %s).', $commandId));

            return self::SUCCESS;
        });
    }
}
