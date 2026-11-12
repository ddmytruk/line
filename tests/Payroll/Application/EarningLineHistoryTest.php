<?php

declare(strict_types=1);

namespace Tests\Payroll\Application;

use Payroll\Application\Command\AddManualAdjustment;
use Payroll\Application\Command\EarningLines;
use Payroll\Application\Command\RecordCalculatedValue;
use Payroll\Application\History\EarningLineHistory;
use Payroll\Application\History\EarningLineHistoryProjector;
use Payroll\Application\History\HistoryEntry;
use Payroll\Domain\EarningLineId;
use Payroll\Domain\Event\SystemValueCalculated;
use Payroll\Domain\Event\SystemValueRecalculated;
use Payroll\Domain\Exception\ZeroAdjustment;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\Support\Payroll\InMemoryPayroll;
use Tests\Support\Payroll\Lines;

/** The history a specialist reads: the current value, every fact that produced it, and the drift of a frozen line. */
#[CoversClass(EarningLineHistoryProjector::class)]
final class EarningLineHistoryTest extends TestCase
{
    private InMemoryPayroll $payroll;

    private int $commands = 0;

    protected function setUp(): void
    {
        $this->payroll = new InMemoryPayroll();
    }

    public function testTheReferenceScenarioReadsAsItsHistory(): void
    {
        $this->calculate('1000.00');
        $this->calculate('1050.00');
        $this->adjust('-45.55', 'Dental plan waived: benefit amount removed');
        $this->calculate('1100.00');
        $this->adjust('+100.10', 'Approved overtime missing from the source data');
        $this->adjust('-0.10', 'Rounding');
        $this->adjust('-0.20', 'Rounding, second pass');
        $this->adjust('+0.20', 'Reverts adjustment #4, entered by mistake');

        $history = $this->payroll->history();

        self::assertSame(
            [
                ['calculated', '1000.00', '1000.00', null, null],
                ['recalculated', '1050.00', '1050.00', null, null],
                ['adjusted', '-45.55', '1004.45', 1, null],
                ['recalculation ignored', '1100.00', '1004.45', null, '50.00'],
                ['adjusted', '100.10', '1104.55', 2, null],
                ['adjusted', '-0.10', '1104.45', 3, null],
                ['adjusted', '-0.20', '1104.25', 4, null],
                ['adjusted', '0.20', '1104.45', 5, null],
            ],
            array_map(static fn(HistoryEntry $entry): array => [
                $entry->kind->value,
                $entry->amount->toDecimalString(),
                $entry->currentValueAfter->toDecimalString(),
                $entry->adjustmentNumber,
                $entry->drift?->toDecimalString(),
            ], $history->entries),
        );
        self::assertSame('1050.00', $history->systemValue->toDecimalString(), 'frozen system value');
        self::assertSame('1104.45', $history->currentValue->toDecimalString());
        self::assertTrue($history->frozen);
        self::assertSame('50.00', $history->drift()->toDecimalString(), 'the calculation now says 1,100.00');
        self::assertSame(8, $history->version);
        self::assertSame('Reverts adjustment #4, entered by mistake', $history->adjustments()[4]->comment);
        self::assertSame('specialist-1', $history->adjustments()[4]->specialistId);
    }

    public function testAnOpenLineHasNoDrift(): void
    {
        $this->calculate('1000.00');
        $this->calculate('1050.00');

        self::assertSame('0.00', $this->payroll->history()->drift()->toDecimalString());
        self::assertFalse($this->payroll->history()->frozen);
    }

    public function testDriftGoesBackToZeroWhenTheCalculationReturnsToTheFrozenValue(): void
    {
        $this->calculate('1050.00');
        $this->adjust('-45.55');
        $this->calculate('1100.00');
        $this->calculate('1050.00');

        $history = $this->payroll->history();
        self::assertSame('0.00', $history->drift()->toDecimalString());
        self::assertSame(['50.00', '0.00'], array_map(
            static fn(HistoryEntry $entry): ?string => $entry->drift?->toDecimalString(),
            \array_slice($history->entries, 2),
        ), 'the history keeps both recalculations');
    }

    public function testARefusedCommandLeavesTheHistoryAsItWas(): void
    {
        $this->calculate('1050.00');
        $before = $this->payroll->history();

        try {
            $this->adjust('0.00');
            self::fail('A zero adjustment must be refused.');
        } catch (ZeroAdjustment) {
        }

        self::assertSame($before, $this->payroll->history());
    }

    public function testTheHistoryShowsWhatAnotherWriterSavedFirst(): void
    {
        $this->calculate('1000.00');
        $this->payroll->anotherWriterFirst(static function (EarningLines $lines): void {
            $line = $lines->get(Lines::id());
            Lines::adjust($line, '+10.00');
            $lines->save($line);
        });

        $this->calculate('1050.00');

        self::assertSame(['calculated', 'adjusted', 'recalculation ignored'], array_map(
            static fn(HistoryEntry $entry): string => $entry->kind->value,
            $this->payroll->history()->entries,
        ));
    }

    public function testARebuiltHistoryEqualsTheOneBuiltAlongTheWay(): void
    {
        $this->calculate('1000.00');
        $this->adjust('-45.55');
        $this->calculate('1100.00');
        $built = $this->payroll->history();

        $this->payroll->rebuild->run();

        self::assertEquals($built, $this->payroll->history());
    }

    public function testAChangeOfOneLineLeavesTheHistoryOfAnotherAsItWas(): void
    {
        $other = '019a7b2c-1111-7000-8000-000000000000';
        $this->payroll->bus->dispatch(new RecordCalculatedValue('other-line', $other, '500.00', 'USD'));
        $before = $this->payroll->history($other);

        $this->calculate('1000.00');
        $this->adjust('-45.55');

        self::assertEquals($before, $this->payroll->history($other));
    }

    public function testARebuildForgetsHistoriesThatNoStoredEventsSupport(): void
    {
        $this->calculate('1000.00');
        $stray = EarningLineHistory::start('019a7b2c-0000-7000-8000-000000000000', Lines::at(), Lines::usd('5.00'));
        $this->payroll->histories->save($stray);

        $this->payroll->rebuild->run();

        self::assertNull($this->payroll->histories->find($stray->lineId));
        self::assertSame('1000.00', $this->payroll->history()->currentValue->toDecimalString());
    }

    public function testAFailedRebuildLeavesTheHistoriesAsTheyWere(): void
    {
        $this->calculate('1000.00');
        $built = $this->payroll->history();
        $this->payroll->store->append('019a7b2c-0000-7000-8000-000000000000', 0, [
            new SystemValueRecalculated(new EarningLineId('019a7b2c-0000-7000-8000-000000000000'), Lines::usd('1.00'), Lines::at()),
        ]);

        try {
            $this->payroll->rebuild->run();
            self::fail('A stream the projector cannot follow must stop the rebuild.');
        } catch (\LogicException) {
        }

        self::assertSame($built, $this->payroll->history());
    }

    public function testASecondFirstCalculationOfALineMeansTheModelIsOutOfStep(): void
    {
        $this->calculate('1000.00');

        $this->expectException(\LogicException::class);
        $this->payroll->projector->project(new SystemValueCalculated(Lines::id(), Lines::usd('5.00'), Lines::at()));
    }

    public function testAnEventOfALineWithoutHistoryMeansTheModelMustBeRebuilt(): void
    {
        $this->expectException(\LogicException::class);
        $this->payroll->projector->project(new SystemValueRecalculated(Lines::id(), Lines::usd('1.00'), Lines::at()));
    }

    private function calculate(string $amount): void
    {
        $this->payroll->bus->dispatch(new RecordCalculatedValue($this->nextId(), Lines::LINE_ID, $amount, 'USD'));
    }

    private function adjust(string $amount, string $comment = 'Correction'): void
    {
        $this->payroll->bus->dispatch(new AddManualAdjustment(
            $this->nextId(),
            Lines::LINE_ID,
            $this->payroll->history()->version,
            $amount,
            'USD',
            $comment,
            'specialist-1',
        ));
    }

    private function nextId(): string
    {
        return 'command-' . ++$this->commands;
    }
}
