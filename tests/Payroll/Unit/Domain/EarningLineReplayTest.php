<?php

declare(strict_types=1);

namespace Tests\Payroll\Unit\Domain;

use Common\Domain\DomainEvent;
use Payroll\Domain\AdjustmentComment;
use Payroll\Domain\Currency;
use Payroll\Domain\EarningLine;
use Payroll\Domain\EarningLineId;
use Payroll\Domain\Event\ManualAdjustmentAdded;
use Payroll\Domain\Event\SystemRecalculationIgnored;
use Payroll\Domain\Event\SystemValueCalculated;
use Payroll\Domain\Event\SystemValueRecalculated;
use Payroll\Domain\Money;
use Payroll\Domain\SpecialistId;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Support\Common\SomethingHappened;
use Tests\Support\Payroll\Lines;

/** A line rebuilt from its history equals the line that recorded it, and a corrupted history is refused. */
#[CoversClass(EarningLine::class)]
final class EarningLineReplayTest extends TestCase
{
    public function testARebuiltLineContinuesWhereTheOriginalStopped(): void
    {
        $original = Lines::calculated('1000.00');
        $original->recordSystemValue(Lines::usd('1050.00'), Lines::at());
        Lines::adjust($original, '-45.55');
        $original->recordSystemValue(Lines::usd('1100.00'), Lines::at());
        Lines::adjust($original, '+100.10');

        $rebuilt = EarningLine::reconstitute($original->releaseEvents());
        Lines::adjust($rebuilt, '-0.10');
        $rebuilt->recordSystemValue(Lines::usd('1100.00'), Lines::at());

        self::assertSame('1104.45', $rebuilt->currentValue()->toDecimalString());
        self::assertSame('1050.00', $rebuilt->systemValue()->toDecimalString());
        self::assertSame(3, $rebuilt->adjustments()[2]->number);
        self::assertCount(1, $rebuilt->releaseEvents(), 'The repeated calculated value must not be recorded again.');
        self::assertSame(6, $rebuilt->version());
    }

    /** @return iterable<string, array{list<DomainEvent>}> */
    public static function corruptedHistories(): iterable
    {
        $id = new EarningLineId(Lines::LINE_ID);
        $at = Lines::at();
        $calculated = new SystemValueCalculated($id, Lines::usd('1000'), $at);
        $adjustment = static fn(int $number, string $amount = '1'): ManualAdjustmentAdded => new ManualAdjustmentAdded(
            $id,
            $number,
            Lines::usd($amount),
            new AdjustmentComment('Correction'),
            new SpecialistId('anna'),
            $at,
        );

        yield 'does not start with a calculation' => [[$adjustment(1)]];
        yield 'calculated twice' => [[$calculated, $calculated]];
        yield 'recalculated after freezing' => [[$calculated, $adjustment(1), new SystemValueRecalculated($id, Lines::usd('1100'), $at)]];
        yield 'ignored recalculation of an open line' => [[$calculated, new SystemRecalculationIgnored($id, Lines::usd('1100'), $at)]];
        yield 'adjustment number skipped' => [[$calculated, $adjustment(2)]];
        yield 'adjustment number repeated' => [[$calculated, $adjustment(1), $adjustment(1)]];
        yield 'foreign event type' => [[$calculated, new SomethingHappened(Lines::LINE_ID, 'unknown')]];
    }

    /** @param list<DomainEvent> $history */
    #[DataProvider('corruptedHistories')]
    public function testRefusesACorruptedHistory(array $history): void
    {
        $this->expectException(\UnexpectedValueException::class);

        EarningLine::reconstitute($history);
    }

    /** @return iterable<string, array{DomainEvent}> */
    public static function eventsInAnotherCurrency(): iterable
    {
        $id = new EarningLineId(Lines::LINE_ID);
        $eur = Money::fromDecimal('1', Currency::EUR);

        yield 'recalculation' => [new SystemValueRecalculated($id, $eur, Lines::at())];
        yield 'ignored recalculation' => [new SystemRecalculationIgnored($id, $eur, Lines::at())];
        yield 'adjustment' => [new ManualAdjustmentAdded($id, 2, $eur, new AdjustmentComment('Euro'), new SpecialistId('anna'), Lines::at())];
    }

    #[DataProvider('eventsInAnotherCurrency')]
    public function testRefusesAHistoryThatMixesCurrencies(DomainEvent $event): void
    {
        $id = new EarningLineId(Lines::LINE_ID);
        $history = [new SystemValueCalculated($id, Lines::usd('1000'), Lines::at())];

        if (!$event instanceof SystemValueRecalculated) {
            $history[] = new ManualAdjustmentAdded($id, 1, Lines::usd('1'), new AdjustmentComment('Freeze'), new SpecialistId('anna'), Lines::at());
        }

        $this->expectException(\DomainException::class);

        EarningLine::reconstitute([...$history, $event]);
    }
}
