<?php

declare(strict_types=1);

namespace App\Modules\Payroll\Cli;

use App\Modules\Payroll\InMemory\InMemoryPayrollModule;
use Common\Application\Command\Command;
use Common\Application\Exception\ConcurrencyConflict;
use Payroll\Application\Command\AddManualAdjustment;
use Payroll\Application\Command\RecordCalculatedValue;
use Payroll\Application\History\EarningLineHistory;
use Payroll\Application\Query\GetEarningLineHistory;
use Payroll\Domain\Currency;
use Payroll\Domain\Money;

/**
 * Plays the reference scenario of docs/payroll/domain.md as commands through the command bus and prints the history
 * read from the model for reading. Then it shows what the bus protects against: a repeated command, an adjustment
 * of an outdated version, and a rebuild of the history. It shows the scenario and checks nothing.
 */
final readonly class DemoCommand
{
    private const string LINE_ID = '019a7b2c-3d4e-7f80-9a1b-2c3d4e5f6a7b';

    /** @var list<array{string, string, string}> step: [action, amount, comment] */
    private const array STEPS = [
        ['calculate', '1000.00', ''],
        ['calculate', '1050.00', ''],
        ['adjust', '-45.55', 'Dental plan waived: benefit amount removed'],
        ['calculate', '1100.00', ''],
        ['adjust', '+100.10', 'Approved overtime missing from the source data'],
        ['adjust', '-0.10', 'Rounding'],
        ['adjust', '-0.20', 'Rounding, second pass'],
        ['adjust', '+0.20', 'Reverts adjustment #4, entered by mistake'],
    ];

    private DemoClock $clock;

    private InMemoryPayrollModule $payroll;

    public function __construct(\DateTimeImmutable $startedAt)
    {
        $this->clock = new DemoClock($startedAt);
        $this->payroll = new InMemoryPayrollModule($this->clock);
    }

    public function run(): string
    {
        $steps = [];
        $commands = [];

        foreach (self::STEPS as $index => [$action, $amount, $comment]) {
            $commandId = 'step-' . ($index + 1);
            $commands[] = $command = $action === 'calculate'
                ? new RecordCalculatedValue($commandId, self::LINE_ID, $amount, 'USD')
                : new AddManualAdjustment($commandId, self::LINE_ID, $this->history()->version, $amount, 'USD', $comment, 'payroll-specialist');
            $this->payroll->bus->dispatch($command);
            $this->clock->nextStep();

            $history = $this->history();
            $description = match (true) {
                $action === 'adjust' => \sprintf('Adjusted %s "%s"', MoneyFormat::signed(self::usd($amount)), $comment),
                $index === 0 => 'Calculated ' . MoneyFormat::amount(self::usd($amount)),
                default => 'Recalculated ' . MoneyFormat::amount(self::usd($amount)) . ($history->frozen ? ' (ignored: the line is frozen)' : ''),
            };
            $steps[] = \sprintf('%d. %s -> current value %s', $index + 1, $description, MoneyFormat::amount($history->currentValue));
        }

        return "Steps\n" . implode("\n", $steps) . "\n\n"
            . "History\n" . HistoryView::history($this->history()) . "\n"
            . "Full log\n" . HistoryView::log($this->history()) . "\n"
            . "Protection\n" . $this->protection($commands[4]);
    }

    /** What the command bus and the history protect against, shown on the line of the scenario. */
    private function protection(Command $sentBefore): string
    {
        $version = $this->history()->version;
        $lines = [];

        $this->payroll->bus->dispatch($sentBefore);
        $lines[] = \sprintf('- Step 5 sent again under the same command id: nothing changed, the line stays at version %d.', $this->history()->version);

        try {
            $this->payroll->bus->dispatch(new AddManualAdjustment('late', self::LINE_ID, $version - 1, '+0.20', 'USD', 'Same fix, by a second specialist', 'second-specialist'));
            $lines[] = '- An adjustment of an outdated version was accepted.';
        } catch (ConcurrencyConflict) {
            $lines[] = \sprintf('- A second specialist who still saw version %d adjusts the line: refused, the line changed since.', $version - 1);
        }

        $built = HistoryView::history($this->history()) . HistoryView::log($this->history());
        $this->payroll->histories->clear();
        $this->payroll->rebuild->run();
        $lines[] = $built === HistoryView::history($this->history()) . HistoryView::log($this->history())
            ? '- The history is dropped and rebuilt from all stored events: it equals the one built along the way.'
            : '- The rebuilt history differs from the one built along the way.';

        return implode("\n", $lines) . "\n";
    }

    private function history(): EarningLineHistory
    {
        return ($this->payroll->getEarningLineHistory)(new GetEarningLineHistory(self::LINE_ID));
    }

    private static function usd(string $amount): Money
    {
        return Money::fromDecimal($amount, Currency::USD);
    }
}
