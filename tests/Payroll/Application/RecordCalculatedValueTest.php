<?php

declare(strict_types=1);

namespace Tests\Payroll\Application;

use Payroll\Application\Command\EarningLines;
use Payroll\Application\Command\RecordCalculatedValue;
use Payroll\Application\Command\RecordCalculatedValueHandler;
use Payroll\Application\Exception\UnknownCurrency;
use Payroll\Domain\Currency;
use Payroll\Domain\EarningLine;
use Payroll\Domain\EarningLineId;
use Payroll\Domain\Event\SystemValueCalculated;
use Payroll\Domain\Exception\InvalidMoneyAmount;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\Support\Payroll\InMemoryPayroll;
use Tests\Support\Payroll\Lines;

/** The calculation sends whole values; the first one creates the line, later ones go through the rules of the line. */
#[CoversClass(RecordCalculatedValueHandler::class)]
final class RecordCalculatedValueTest extends TestCase
{
    private InMemoryPayroll $payroll;

    protected function setUp(): void
    {
        $this->payroll = new InMemoryPayroll();
    }

    public function testTheFirstValueCreatesTheLineAtTheTimeOfTheClock(): void
    {
        $this->payroll->clock->set('2026-03-01 10:30:00');
        $this->record('c-1', '1000.00');

        $events = $this->payroll->store->load(Lines::LINE_ID);
        self::assertCount(1, $events);
        self::assertInstanceOf(SystemValueCalculated::class, $events[0]);
        self::assertSame('1000.00', $events[0]->value->toDecimalString());
        self::assertSame('2026-03-01T10:30:00+00:00', $events[0]->occurredAt()->format(\DATE_ATOM));
    }

    public function testALaterValueRecalculatesTheLine(): void
    {
        $this->record('c-1', '1000.00');
        $this->record('c-2', '1050.00');

        self::assertSame('1050.00', $this->payroll->line()->currentValue()->toDecimalString());
    }

    public function testTheSameValueSentAgainUnderANewIdRecordsNothing(): void
    {
        $this->record('c-1', '1000.00');
        $this->record('c-2', '1000.00');

        self::assertSame(1, $this->payroll->versionOf());
    }

    public function testTwoFirstValuesAtOnceBecomeACalculationAndARecalculation(): void
    {
        // Another process creates the line between our check and our save; the bus runs the command again on the new state.
        $this->payroll->anotherWriterFirst(static function (EarningLines $lines): void {
            $lines->save(EarningLine::calculate(new EarningLineId(Lines::LINE_ID), Lines::usd('1000.00'), Lines::at()));
        });

        $this->record('c-1', '1050.00');

        self::assertSame(2, $this->payroll->versionOf());
        self::assertSame('1050.00', $this->payroll->line()->currentValue()->toDecimalString());
    }

    public function testRecordsTheValueInTheCurrencyTheCalculationSent(): void
    {
        $this->payroll->bus->dispatch(new RecordCalculatedValue('c-1', Lines::LINE_ID, '1000.00', 'EUR'));

        self::assertSame(Currency::EUR, $this->payroll->line()->currentValue()->currency);
    }

    public function testRefusesAnUnknownCurrencyAndRecordsNothing(): void
    {
        try {
            $this->payroll->bus->dispatch(new RecordCalculatedValue('c-1', Lines::LINE_ID, '1000.00', 'XYZ'));
            self::fail('An unknown currency must be refused.');
        } catch (UnknownCurrency) {
        }

        self::assertSame(0, $this->payroll->versionOf());
    }

    public function testRefusesAnInexactAmountAndRecordsNothing(): void
    {
        try {
            $this->record('c-1', '1000.005');
            self::fail('An inexact amount must be refused.');
        } catch (InvalidMoneyAmount) {
        }

        self::assertSame(0, $this->payroll->versionOf());
    }

    private function record(string $commandId, string $amount): void
    {
        $this->payroll->bus->dispatch(new RecordCalculatedValue($commandId, Lines::LINE_ID, $amount, 'USD'));
    }
}
