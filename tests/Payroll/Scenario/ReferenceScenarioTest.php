<?php

declare(strict_types=1);

namespace Tests\Payroll\Scenario;

use Common\Domain\DomainEvent;
use Payroll\Domain\EarningLine;
use Payroll\Domain\Event\ManualAdjustmentAdded;
use Payroll\Domain\Event\SystemRecalculationIgnored;
use Payroll\Domain\Event\SystemValueCalculated;
use Payroll\Domain\Event\SystemValueRecalculated;
use Payroll\Domain\ManualAdjustment;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Support\Payroll\Lines;

/**
 * The reference scenario of docs/payroll/domain.md as an executable specification:
 * every intermediate value and the final history are checked.
 */
#[CoversClass(EarningLine::class)]
final class ReferenceScenarioTest extends TestCase
{
    /**
     * The value the calculation sends in step 4 does not matter: the line is frozen by then.
     *
     * @return iterable<string, array{string}>
     */
    public static function ignoredRecalculations(): iterable
    {
        yield 'higher value' => ['1100.00'];
        yield 'lower value' => ['900.00'];
        yield 'back to the first value' => ['1000.00'];
        yield 'same as the frozen value' => ['1050.00'];
    }

    #[DataProvider('ignoredRecalculations')]
    public function testEveryStepAndTheFinalHistory(string $step4Value): void
    {
        $line = Lines::calculated('1000.00');
        self::assertValue('1000.00', $line, 'step 1');

        $line->recordSystemValue(Lines::usd('1050.00'), Lines::at());
        self::assertValue('1050.00', $line, 'step 2: the open line takes the recalculated value');

        Lines::adjust($line, '-45.55', 'Dental plan waived: benefit amount removed');
        self::assertValue('1004.45', $line, 'step 3: adjustment #1 freezes the line');

        $line->recordSystemValue(Lines::usd($step4Value), Lines::at());
        self::assertValue('1004.45', $line, 'step 4: the frozen line ignores the recalculation');

        Lines::adjust($line, '+100.10', 'Approved overtime missing from the source data');
        self::assertValue('1104.55', $line, 'step 5');

        Lines::adjust($line, '-0.10', 'Rounding');
        self::assertValue('1104.45', $line, 'step 6');

        Lines::adjust($line, '-0.20', 'Rounding, second pass');
        self::assertValue('1104.25', $line, 'step 7');

        Lines::adjust($line, '+0.20', 'Reverts adjustment #4, entered by mistake');
        self::assertValue('1104.45', $line, 'step 8: adjustment #5 compensates #4');

        self::assertSame('1050.00', $line->systemValue()->toDecimalString(), 'frozen system value');
        self::assertSame(
            [[1, '-45.55'], [2, '100.10'], [3, '-0.10'], [4, '-0.20'], [5, '0.20']],
            array_map(
                static fn(ManualAdjustment $adjustment): array => [$adjustment->number, $adjustment->amount->toDecimalString()],
                $line->adjustments(),
            ),
        );
        self::assertSame('1104.45', $line->currentValue()->toDecimalString(), 'current value');

        // The full log keeps the first value and the ignored recalculation; a repeated value is not recorded.
        $events = $line->releaseEvents();
        self::assertSame(
            [
                ['Calculated', '1000.00'],
                ['Recalculated', '1050.00'],
                ['Adjusted', '-45.55'],
                ...($step4Value === '1050.00' ? [] : [['Ignored', $step4Value]]),
                ['Adjusted', '100.10'],
                ['Adjusted', '-0.10'],
                ['Adjusted', '-0.20'],
                ['Adjusted', '0.20'],
            ],
            array_map(static fn(DomainEvent $event): array => match (true) {
                $event instanceof SystemValueCalculated => ['Calculated', $event->value->toDecimalString()],
                $event instanceof SystemValueRecalculated => ['Recalculated', $event->value->toDecimalString()],
                $event instanceof SystemRecalculationIgnored => ['Ignored', $event->calculatedValue->toDecimalString()],
                $event instanceof ManualAdjustmentAdded => ['Adjusted', $event->amount->toDecimalString()],
                default => [$event::class, ''],
            }, $events),
        );

        $rebuilt = EarningLine::reconstitute($events);
        self::assertSame('1104.45', $rebuilt->currentValue()->toDecimalString(), 'the full log rebuilds the same line');
    }

    private static function assertValue(string $expected, EarningLine $line, string $step): void
    {
        self::assertSame($expected, $line->currentValue()->toDecimalString(), $step);
    }
}
