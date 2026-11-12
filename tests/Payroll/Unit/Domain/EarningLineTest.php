<?php

declare(strict_types=1);

namespace Tests\Payroll\Unit\Domain;

use Payroll\Domain\AdjustmentComment;
use Payroll\Domain\Currency;
use Payroll\Domain\EarningLine;
use Payroll\Domain\EarningLineId;
use Payroll\Domain\Event\ManualAdjustmentAdded;
use Payroll\Domain\Event\SystemRecalculationIgnored;
use Payroll\Domain\Event\SystemValueCalculated;
use Payroll\Domain\Event\SystemValueRecalculated;
use Payroll\Domain\Exception\CurrencyMismatch;
use Payroll\Domain\Exception\InvalidMoneyAmount;
use Payroll\Domain\Exception\ZeroAdjustment;
use Payroll\Domain\ManualAdjustment;
use Payroll\Domain\Money;
use Payroll\Domain\SpecialistId;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\Support\Payroll\Lines;

#[CoversClass(EarningLine::class)]
#[CoversClass(ManualAdjustment::class)]
#[CoversClass(ManualAdjustmentAdded::class)]
#[CoversClass(SystemValueCalculated::class)]
#[CoversClass(SystemValueRecalculated::class)]
#[CoversClass(SystemRecalculationIgnored::class)]
#[CoversClass(ZeroAdjustment::class)]
final class EarningLineTest extends TestCase
{
    public function testACalculatedLineIsOpenAndWorthItsSystemValue(): void
    {
        $line = Lines::calculated('1000.00');

        self::assertSame(Lines::LINE_ID, $line->id()->value);
        self::assertSame('1000.00', $line->currentValue()->toDecimalString());
        self::assertSame('1000.00', $line->systemValue()->toDecimalString());
        self::assertFalse($line->isFrozen());
        self::assertSame([], $line->adjustments());
        self::assertEquals(
            [new SystemValueCalculated(new EarningLineId(Lines::LINE_ID), Lines::usd('1000.00'), Lines::at())],
            $line->releaseEvents(),
        );
    }

    public function testARecalculationReplacesTheValueOfAnOpenLine(): void
    {
        $line = Lines::calculated('1000.00');
        $line->releaseEvents();

        $line->recordSystemValue(Lines::usd('1050.00'), Lines::at());

        self::assertSame('1050.00', $line->currentValue()->toDecimalString());
        self::assertSame('1050.00', $line->systemValue()->toDecimalString());
        self::assertEquals(
            [new SystemValueRecalculated(new EarningLineId(Lines::LINE_ID), Lines::usd('1050.00'), Lines::at())],
            $line->releaseEvents(),
        );
    }

    public function testTheFirstAdjustmentFreezesTheLatestSystemValue(): void
    {
        $line = Lines::calculated('1000.00');
        $line->recordSystemValue(Lines::usd('1050.00'), Lines::at());

        Lines::adjust($line, '-45.55');

        self::assertTrue($line->isFrozen());
        self::assertSame('1050.00', $line->systemValue()->toDecimalString());
        self::assertSame('1004.45', $line->currentValue()->toDecimalString());
    }

    public function testAnAdjustmentRecordsEverythingTheHistoryNeeds(): void
    {
        $line = Lines::calculated('1000.00');
        $line->releaseEvents();

        $line->addManualAdjustment(
            Lines::usd('+100.10'),
            new AdjustmentComment('Overtime approved after the cut-off'),
            new SpecialistId('anna'),
            new \DateTimeImmutable('2026-01-15 11:00:00', new \DateTimeZone('Europe/Kyiv')),
        );

        $expected = new ManualAdjustmentAdded(
            new EarningLineId(Lines::LINE_ID),
            1,
            Lines::usd('100.10'),
            new AdjustmentComment('Overtime approved after the cut-off'),
            new SpecialistId('anna'),
            Lines::at('2026-01-15 09:00:00'),
        );
        self::assertEquals([$expected], $line->releaseEvents());
        self::assertEquals(
            [new ManualAdjustment(1, $expected->amount, $expected->comment, $expected->specialistId, $expected->occurredAt)],
            $line->adjustments(),
        );
    }

    public function testTimesAreRecordedInUtc(): void
    {
        $kyiv = new \DateTimeImmutable('2026-01-15 11:00:00', new \DateTimeZone('Europe/Kyiv'));
        $line = EarningLine::calculate(new EarningLineId(Lines::LINE_ID), Lines::usd('1000'), $kyiv);
        $line->recordSystemValue(Lines::usd('1050'), $kyiv);
        Lines::adjust($line, '-45.55');
        $line->addManualAdjustment(Lines::usd('1'), new AdjustmentComment('Kyiv'), new SpecialistId('anna'), $kyiv);
        $line->recordSystemValue(Lines::usd('1100'), $kyiv);

        $events = $line->releaseEvents();

        foreach ([0, 1, 3, 4] as $index) {
            self::assertSame('2026-01-15T09:00:00+00:00', $events[$index]->occurredAt()->format(\DATE_ATOM), 'event #' . $index);
        }
        self::assertSame('2026-01-15T09:00:00+00:00', $line->adjustments()[1]->addedAt->format(\DATE_ATOM));
    }

    public function testARecalculationOfAFrozenLineIsRecordedButChangesNothing(): void
    {
        $line = Lines::calculated('1050.00');
        Lines::adjust($line, '-45.55');
        $line->releaseEvents();

        $line->recordSystemValue(Lines::usd('1100.00'), Lines::at());

        self::assertSame('1004.45', $line->currentValue()->toDecimalString());
        self::assertSame('1050.00', $line->systemValue()->toDecimalString());
        self::assertEquals(
            [new SystemRecalculationIgnored(new EarningLineId(Lines::LINE_ID), Lines::usd('1100.00'), Lines::at())],
            $line->releaseEvents(),
        );
    }

    public function testALineStaysFrozenWhenItsAdjustmentsAddUpToZero(): void
    {
        $line = Lines::calculated('1050.00');
        Lines::adjust($line, '+10.00');
        Lines::adjust($line, '-10.00');

        $line->recordSystemValue(Lines::usd('1100.00'), Lines::at());

        self::assertTrue($line->isFrozen());
        self::assertSame('1050.00', $line->currentValue()->toDecimalString());
    }

    public function testTheSameValueFromTheCalculationIsNotRecordedAgain(): void
    {
        $open = Lines::calculated('1050.00');
        $open->releaseEvents();
        $open->recordSystemValue(Lines::usd('1050.00'), Lines::at());

        $frozen = Lines::calculated('1050.00');
        Lines::adjust($frozen, '-45.55');
        $frozen->recordSystemValue(Lines::usd('1100.00'), Lines::at());
        $frozen->releaseEvents();
        $frozen->recordSystemValue(Lines::usd('1100.00'), Lines::at());

        self::assertSame([], $open->releaseEvents());
        self::assertSame([], $frozen->releaseEvents());
    }

    public function testAFrozenLineRecordsAReturnToTheFrozenValue(): void
    {
        $line = Lines::calculated('1050.00');
        Lines::adjust($line, '-45.55');
        $line->recordSystemValue(Lines::usd('1100.00'), Lines::at());
        $line->releaseEvents();

        $line->recordSystemValue(Lines::usd('1050.00'), Lines::at());

        self::assertEquals(
            [new SystemRecalculationIgnored(new EarningLineId(Lines::LINE_ID), Lines::usd('1050.00'), Lines::at())],
            $line->releaseEvents(),
        );
    }

    public function testAdjustmentsAreNumberedInTheOrderTheyWereAdded(): void
    {
        $line = Lines::calculated('1050.00');

        Lines::adjust($line, '-0.10', 'First');
        $line->recordSystemValue(Lines::usd('1100.00'), Lines::at());
        Lines::adjust($line, '-0.20', 'Second');

        self::assertSame([1, 2], array_map(static fn(ManualAdjustment $a): int => $a->number, $line->adjustments()));
        self::assertSame(['First', 'Second'], array_map(static fn(ManualAdjustment $a): string => $a->comment->value, $line->adjustments()));
    }

    public function testRejectsAZeroAdjustmentWithoutATrace(): void
    {
        $line = Lines::calculated('1050.00');
        $line->releaseEvents();

        try {
            Lines::adjust($line, '0.00');
            self::fail('A zero adjustment was accepted.');
        } catch (ZeroAdjustment) {
        }

        self::assertFalse($line->isFrozen());
        self::assertSame([], $line->releaseEvents());
    }

    public function testRejectsAnAdjustmentInAnotherCurrency(): void
    {
        $line = Lines::calculated('1050.00');

        $this->expectException(CurrencyMismatch::class);

        $line->addManualAdjustment(Money::fromDecimal('5', Currency::EUR), new AdjustmentComment('Wrong currency'), new SpecialistId('anna'), Lines::at());
    }

    public function testAZeroAdjustmentInAnotherCurrencyIsRejectedForItsCurrency(): void
    {
        $line = Lines::calculated('1050.00');

        $this->expectException(CurrencyMismatch::class);

        $line->addManualAdjustment(Money::fromDecimal('0', Currency::EUR), new AdjustmentComment('Wrong currency'), new SpecialistId('anna'), Lines::at());
    }

    public function testRejectsARecalculationInAnotherCurrency(): void
    {
        $line = Lines::calculated('1050.00');

        $this->expectException(CurrencyMismatch::class);

        $line->recordSystemValue(Money::fromDecimal('1050', Currency::EUR), Lines::at());
    }

    public function testRejectsAnAdjustmentThatWouldLeaveTheSupportedRangeWithoutATrace(): void
    {
        $line = EarningLine::calculate(new EarningLineId(Lines::LINE_ID), Money::ofMinor(Money::MAX_MINOR_UNITS, Currency::USD), Lines::at());
        $line->releaseEvents();

        try {
            Lines::adjust($line, '0.01');
            self::fail('An out-of-range adjustment was accepted.');
        } catch (InvalidMoneyAmount) {
        }

        self::assertSame([], $line->releaseEvents());
        self::assertFalse($line->isFrozen());
    }

    public function testRejectsACalculatedValueTooFarFromTheFrozenValueWithoutATrace(): void
    {
        $line = EarningLine::calculate(new EarningLineId(Lines::LINE_ID), Money::ofMinor(-Money::MAX_MINOR_UNITS, Currency::USD), Lines::at());
        Lines::adjust($line, '1.00');
        $line->releaseEvents();

        try {
            $line->recordSystemValue(Money::ofMinor(Money::MAX_MINOR_UNITS, Currency::USD), Lines::at());
            self::fail('A value whose difference from the frozen value is out of range was accepted.');
        } catch (InvalidMoneyAmount) {
        }

        self::assertSame([], $line->releaseEvents());
    }

    public function testAllowsTheValueToBecomeNegative(): void
    {
        $line = Lines::calculated('100.00');

        Lines::adjust($line, '-150.00', 'Recovering an overpayment');

        self::assertSame('-50.00', $line->currentValue()->toDecimalString());
    }

    public function testOffersNoWayToEditOrRemoveAnAdjustment(): void
    {
        $publicMethods = array_map(
            static fn(\ReflectionMethod $method): string => $method->getName(),
            array_filter(
                new \ReflectionClass(EarningLine::class)->getMethods(\ReflectionMethod::IS_PUBLIC),
                static fn(\ReflectionMethod $method): bool => !$method->isStatic(),
            ),
        );
        sort($publicMethods);

        // Every state-changing method is listed here on purpose: adding one must be a conscious decision.
        self::assertSame(
            ['addManualAdjustment', 'adjustments', 'aggregateId', 'currentValue', 'id', 'isFrozen', 'recordSystemValue', 'releaseEvents', 'systemValue', 'version'],
            $publicMethods,
        );
    }
}
