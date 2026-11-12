<?php

declare(strict_types=1);

namespace Tests\Payroll\Application;

use Payroll\Application\History\EarningLineHistoryProjector;
use Payroll\Application\History\HistoryEntry;
use Payroll\Domain\EarningLine;
use Payroll\Domain\ManualAdjustment;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\Support\Payroll\InMemoryPayroll;
use Tests\Support\Payroll\Lines;
use Tests\Support\Payroll\RandomActions;

/**
 * On many random sequences of actions, the history built for reading agrees with the line rebuilt from its events,
 * and a rebuild of the history gives the same history.
 */
#[CoversClass(EarningLineHistoryProjector::class)]
final class EarningLineHistoryPropertiesTest extends TestCase
{
    public function testTheHistoryAgreesWithTheLineForRandomSequencesOfActions(): void
    {
        $firstSeed = RandomActions::firstSeed();

        for ($run = 0; $run < RandomActions::runs(); ++$run) {
            $generator = new RandomActions($firstSeed + $run);

            try {
                $this->checkOneSequence($generator);
            } catch (\Throwable $error) {
                $replay = \sprintf(' (replay with PROPERTY_SEED=%d PROPERTY_RUNS=1)', $generator->seed);

                throw $error instanceof AssertionFailedError ? new AssertionFailedError($error->getMessage() . $replay) : new \RuntimeException($error->getMessage() . $replay, 0, $error);
            }
        }
    }

    private function checkOneSequence(RandomActions $generator): void
    {
        $payroll = new InMemoryPayroll();
        $first = $generator->systemValue();
        $line = EarningLine::calculate(Lines::id(), $first, Lines::at());
        $payroll->lines->save($line);
        $lastSent = $first;

        foreach ($generator->actions($first) as [$action, $amount]) {
            $line = $payroll->line();

            if ($action === 'recalculate') {
                $line->recordSystemValue($amount, Lines::at());
                $lastSent = $amount;
            } else {
                Lines::adjust($line, $amount->toDecimalString());
            }

            $payroll->lines->save($line);
        }

        $line = $payroll->line();
        $history = $payroll->history();

        self::assertSame($line->version(), $history->version, 'version');
        self::assertSame($line->currentValue()->toDecimalString(), $history->currentValue->toDecimalString(), 'current value');
        self::assertSame($line->systemValue()->toDecimalString(), $history->systemValue->toDecimalString(), 'system value');
        self::assertSame($line->isFrozen(), $history->frozen, 'frozen');
        self::assertSame($lastSent->subtract($line->systemValue())->toDecimalString(), $history->drift()->toDecimalString(), 'drift');
        self::assertSame(
            array_map(static fn(ManualAdjustment $adjustment): array => [$adjustment->number, $adjustment->amount->toDecimalString()], $line->adjustments()),
            array_map(static fn(HistoryEntry $entry): array => [$entry->adjustmentNumber, $entry->amount->toDecimalString()], $history->adjustments()),
            'adjustments',
        );
        self::assertCount($history->version, $history->entries, 'one entry per recorded fact');

        $payroll->rebuild->run();
        self::assertEquals($history, $payroll->history(), 'rebuilt history');
    }
}
