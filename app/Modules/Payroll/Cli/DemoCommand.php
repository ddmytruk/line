<?php

declare(strict_types=1);

namespace App\Modules\Payroll\Cli;

use Payroll\Domain\AdjustmentComment;
use Payroll\Domain\Currency;
use Payroll\Domain\EarningLine;
use Payroll\Domain\EarningLineId;
use Payroll\Domain\Money;
use Payroll\Domain\SpecialistId;

/**
 * Plays the reference scenario of docs/payroll/domain.md step by step and prints the result.
 * It shows the scenario and checks nothing.
 */
final class DemoCommand
{
    private const string LINE_ID = '019a7b2c-3d4e-7f80-9a1b-2c3d4e5f6a7b';

    /** @var list<array{string, string, string}> step: [action, amount, comment] */
    private const array STEPS = [
        ['recalculate', '1050.00', ''],
        ['adjust', '-45.55', 'Dental plan waived: benefit amount removed'],
        ['recalculate', '1100.00', ''],
        ['adjust', '+100.10', 'Approved overtime missing from the source data'],
        ['adjust', '-0.10', 'Rounding'],
        ['adjust', '-0.20', 'Rounding, second pass'],
        ['adjust', '+0.20', 'Reverts adjustment #4, entered by mistake'],
    ];

    public function __construct(
        private readonly \DateTimeImmutable $startedAt,
    ) {}

    public function run(): string
    {
        $at = $this->startedAt;
        $line = EarningLine::calculate(new EarningLineId(self::LINE_ID), self::usd('1000.00'), $at);
        $specialist = new SpecialistId('payroll-specialist');
        $steps = [\sprintf('1. Calculated %s -> current value %s', MoneyFormat::amount($line->systemValue()), MoneyFormat::amount($line->currentValue()))];

        foreach (self::STEPS as $index => [$action, $amount, $comment]) {
            $at = $at->modify('+1 hour');

            if ($action === 'recalculate') {
                $line->recordSystemValue(self::usd($amount), $at);
                $description = 'Recalculated ' . MoneyFormat::amount(self::usd($amount)) . ($line->isFrozen() ? ' (ignored: the line is frozen)' : '');
            } else {
                $line->addManualAdjustment(self::usd($amount), new AdjustmentComment($comment), $specialist, $at);
                $description = \sprintf('Adjusted %s "%s"', MoneyFormat::signed(self::usd($amount)), $comment);
            }

            $steps[] = \sprintf('%d. %s -> current value %s', $index + 2, $description, MoneyFormat::amount($line->currentValue()));
        }

        return "Steps\n" . implode("\n", $steps) . "\n\n"
            . "History\n" . HistoryView::history($line) . "\n"
            . "Full log\n" . HistoryView::log($line->releaseEvents());
    }

    private static function usd(string $amount): Money
    {
        return Money::fromDecimal($amount, Currency::USD);
    }
}
