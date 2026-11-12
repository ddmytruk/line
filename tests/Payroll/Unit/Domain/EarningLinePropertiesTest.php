<?php

declare(strict_types=1);

namespace Tests\Payroll\Unit\Domain;

use Payroll\Domain\AdjustmentComment;
use Payroll\Domain\EarningLine;
use Payroll\Domain\EarningLineId;
use Payroll\Domain\Event\ManualAdjustmentAdded;
use Payroll\Domain\Event\SystemRecalculationIgnored;
use Payroll\Domain\Event\SystemValueCalculated;
use Payroll\Domain\Event\SystemValueRecalculated;
use Payroll\Domain\ManualAdjustment;
use Payroll\Domain\SpecialistId;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\Support\Payroll\Lines;
use Tests\Support\Payroll\RandomActions;

/**
 * Invariants checked on many random sequences of actions, against a deliberately naive model:
 * the model keeps plain integers and recomputes everything from scratch.
 */
#[CoversClass(EarningLine::class)]
final class EarningLinePropertiesTest extends TestCase
{
    public function testInvariantsHoldForRandomSequencesOfActions(): void
    {
        $firstSeed = RandomActions::firstSeed();

        for ($run = 0; $run < RandomActions::runs(); ++$run) {
            $generator = new RandomActions($firstSeed + $run);

            try {
                $this->checkOneSequence($generator);
            } catch (\Throwable $error) {
                // Assertion failures carry the seed already; anything else is wrapped so the run can be replayed.
                throw $error instanceof AssertionFailedError ? $error : new \RuntimeException($error->getMessage() . self::replay($generator), 0, $error);
            }
        }
    }

    private function checkOneSequence(RandomActions $generator): void
    {
        $seed = self::replay($generator);
        $first = $generator->systemValue();
        $line = EarningLine::calculate(new EarningLineId(Lines::LINE_ID), $first, Lines::at());

        // The naive model.
        $systemValue = $first->minorUnits;
        $lastSent = $first->minorUnits;
        $adjustments = [];
        $expectedEvents = [SystemValueCalculated::class];

        foreach ($generator->actions($first) as [$action, $amount]) {
            if ($action === 'recalculate') {
                $line->recordSystemValue($amount, Lines::at());

                if ($amount->minorUnits !== $lastSent) {
                    $expectedEvents[] = $adjustments === [] ? SystemValueRecalculated::class : SystemRecalculationIgnored::class;
                    $systemValue = $adjustments === [] ? $amount->minorUnits : $systemValue;
                }
                $lastSent = $amount->minorUnits;
            } else {
                $line->addManualAdjustment($amount, new AdjustmentComment('Generated'), new SpecialistId('generator'), Lines::at());
                $adjustments[] = $amount->minorUnits;
                $expectedEvents[] = ManualAdjustmentAdded::class;
            }

            // Current value = system value + sum of adjustments; the system value is frozen at the first adjustment.
            self::assertSame($systemValue, $line->systemValue()->minorUnits, 'system value' . $seed);
            self::assertSame($systemValue + array_sum($adjustments), $line->currentValue()->minorUnits, 'current value' . $seed);
            self::assertSame($adjustments !== [], $line->isFrozen(), 'frozen' . $seed);
        }

        // Adjustments are kept in order, numbered 1..N, and never change.
        self::assertSame($adjustments === [] ? [] : range(1, \count($adjustments)), self::numbers($line), 'numbering' . $seed);
        self::assertSame($adjustments, array_map(static fn(ManualAdjustment $a): int => $a->amount->minorUnits, $line->adjustments()), 'amounts' . $seed);

        // Only changes are recorded, and replaying them rebuilds the same line.
        $events = $line->releaseEvents();
        self::assertSame($expectedEvents, array_map(static fn(object $event): string => $event::class, $events), 'recorded events' . $seed);

        $rebuilt = EarningLine::reconstitute($events);
        self::assertTrue($rebuilt->currentValue()->equals($line->currentValue()), 'rebuilt current value' . $seed);
        self::assertTrue($rebuilt->systemValue()->equals($line->systemValue()), 'rebuilt system value' . $seed);
        self::assertEquals($line->adjustments(), $rebuilt->adjustments(), 'rebuilt adjustments' . $seed);
        self::assertSame($line->version(), $rebuilt->version(), 'rebuilt version' . $seed);
    }

    private static function replay(RandomActions $generator): string
    {
        return \sprintf(' (replay with PROPERTY_SEED=%d PROPERTY_RUNS=1 make test)', $generator->seed);
    }

    /** @return list<int> */
    private static function numbers(EarningLine $line): array
    {
        return array_map(static fn(ManualAdjustment $a): int => $a->number, $line->adjustments());
    }

}
