<?php

declare(strict_types=1);

namespace Tests\Payroll\Application;

use Common\Application\Exception\CommandIdReused;
use Common\Application\Exception\ConcurrencyConflict;
use Payroll\Application\Command\AddManualAdjustment;
use Payroll\Application\Command\AddManualAdjustmentHandler;
use Payroll\Application\Command\EarningLines;
use Payroll\Application\Command\RecordCalculatedValue;
use Payroll\Application\Exception\EarningLineNotFound;
use Payroll\Application\Exception\UnknownCurrency;
use Payroll\Domain\Exception\CurrencyMismatch;
use Payroll\Domain\Exception\InvalidAdjustmentComment;
use Payroll\Domain\Exception\ZeroAdjustment;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\Support\Payroll\InMemoryPayroll;
use Tests\Support\Payroll\Lines;

/** A specialist adjusts the version of the line they saw; anything else is refused without a trace. */
#[CoversClass(AddManualAdjustmentHandler::class)]
final class AddManualAdjustmentTest extends TestCase
{
    private InMemoryPayroll $payroll;

    protected function setUp(): void
    {
        $this->payroll = new InMemoryPayroll();
        $this->payroll->bus->dispatch(new RecordCalculatedValue('calc-1', Lines::LINE_ID, '1050.00', 'USD'));
    }

    public function testAdjustsTheLineAndRecordsTheAuthorAndTheTimeOfTheClock(): void
    {
        $this->payroll->clock->set('2026-03-02 14:00:00');
        $this->adjust('anna-1', version: 1, amount: '-45.55');

        $adjustment = $this->payroll->line()->adjustments()[0];
        self::assertSame('-45.55', $adjustment->amount->toDecimalString());
        self::assertSame('anna', $adjustment->specialistId->value);
        self::assertSame('2026-03-02T14:00:00+00:00', $adjustment->addedAt->format(\DATE_ATOM));
        self::assertSame('1004.45', $this->payroll->line()->currentValue()->toDecimalString());
    }

    public function testTheSameAdjustmentSentTwiceIsAddedOnce(): void
    {
        $this->adjust('anna-1', version: 1, amount: '-45.55');
        $this->adjust('anna-1', version: 1, amount: '-45.55');

        self::assertCount(1, $this->payroll->line()->adjustments());
    }

    public function testRefusesAReusedCommandIdWithAnotherAmount(): void
    {
        $this->adjust('anna-1', version: 1, amount: '-45.55');

        $this->expectException(CommandIdReused::class);
        $this->adjust('anna-1', version: 1, amount: '-50.00');
    }

    public function testTwoSpecialistsWhoSawTheSameVersionCannotBothAdjust(): void
    {
        $this->adjust('anna-1', version: 1, amount: '-45.55', specialist: 'anna');

        try {
            $this->adjust('boris-1', version: 1, amount: '-45.55', specialist: 'boris');
            self::fail('An adjustment of an outdated version must be refused.');
        } catch (ConcurrencyConflict) {
        }

        self::assertCount(1, $this->payroll->line()->adjustments());
        self::assertSame('1004.45', $this->payroll->line()->currentValue()->toDecimalString());
    }

    public function testRefusesAnAdjustmentWhenTheLineChangesBetweenLoadingAndSaving(): void
    {
        $this->payroll->anotherWriterFirst(static function (EarningLines $lines): void {
            $line = $lines->get(Lines::id());
            Lines::adjust($line, '+10.00');
            $lines->save($line);
        });

        try {
            $this->adjust('anna-1', version: 1, amount: '-45.55');
            self::fail('An adjustment decided on an outdated line must be refused, not retried.');
        } catch (ConcurrencyConflict) {
        }

        self::assertSame(['10.00'], array_map(
            static fn($adjustment): string => $adjustment->amount->toDecimalString(),
            $this->payroll->line()->adjustments(),
        ));
    }

    public function testRefusesAVersionTheLineHasNotReached(): void
    {
        $this->expectException(ConcurrencyConflict::class);
        $this->adjust('anna-1', version: 5, amount: '-45.55');
    }

    public function testRefusesToAdjustALineThatDoesNotExist(): void
    {
        $this->expectException(EarningLineNotFound::class);
        $this->payroll->bus->dispatch(new AddManualAdjustment('anna-1', '019a7b2c-0000-7000-8000-000000000000', 0, '-45.55', 'USD', 'Correction', 'anna'));
    }

    public function testRefusesAnInvalidAdjustmentWithoutATrace(): void
    {
        foreach ([[ZeroAdjustment::class, '0.00', 'Correction'], [InvalidAdjustmentComment::class, '-45.55', '   ']] as $i => [$expected, $amount, $comment]) {
            try {
                $this->adjust('anna-' . $i, version: 1, amount: $amount, comment: $comment);
                self::fail(\sprintf('%s expected.', $expected));
            } catch (\DomainException $refused) {
                self::assertInstanceOf($expected, $refused);
            }
        }

        self::assertSame(1, $this->payroll->versionOf());
    }

    public function testARefusedAdjustmentCanBeCorrectedAndSentAgainUnderTheSameId(): void
    {
        try {
            $this->adjust('anna-1', version: 1, amount: '-45.55', comment: '   ');
            self::fail('A blank comment must be refused.');
        } catch (InvalidAdjustmentComment) {
        }

        $this->adjust('anna-1', version: 1, amount: '-45.55', comment: 'Benefit no longer applies');

        self::assertSame('Benefit no longer applies', $this->payroll->line()->adjustments()[0]->comment->value);
    }

    public function testRefusesAnUnknownCurrencyWithoutATrace(): void
    {
        try {
            $this->payroll->bus->dispatch(new AddManualAdjustment('anna-1', Lines::LINE_ID, 1, '-45.55', 'XYZ', 'Correction', 'anna'));
            self::fail('An unknown currency must be refused.');
        } catch (UnknownCurrency) {
        }

        self::assertSame(1, $this->payroll->versionOf());
    }

    public function testRefusesAnAmountInAnotherCurrencyThanTheLine(): void
    {
        $this->expectException(CurrencyMismatch::class);

        $this->payroll->bus->dispatch(new AddManualAdjustment('anna-1', Lines::LINE_ID, 1, '-45.55', 'EUR', 'Correction', 'anna'));
    }

    private function adjust(string $commandId, int $version, string $amount, string $specialist = 'anna', string $comment = 'Correction'): void
    {
        $this->payroll->bus->dispatch(new AddManualAdjustment($commandId, Lines::LINE_ID, $version, $amount, 'USD', $comment, $specialist));
    }
}
