<?php

declare(strict_types=1);

namespace Tests\Payroll\Application;

use Payroll\Application\Command\RecordCalculatedValue;
use Payroll\Application\Exception\EarningLineNotFound;
use Payroll\Application\Query\GetEarningLineHistory;
use Payroll\Application\Query\GetEarningLineHistoryHandler;
use Payroll\Domain\Exception\InvalidIdentifier;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\Support\Payroll\InMemoryPayroll;
use Tests\Support\Payroll\Lines;

/** Reading the history of a line goes through one query. */
#[CoversClass(GetEarningLineHistoryHandler::class)]
final class GetEarningLineHistoryTest extends TestCase
{
    public function testReturnsTheHistoryOfALine(): void
    {
        $payroll = new InMemoryPayroll();
        $payroll->bus->dispatch(new RecordCalculatedValue('calculation-1', Lines::LINE_ID, '1000.00', 'USD'));

        $history = ($payroll->getEarningLineHistory)(new GetEarningLineHistory(Lines::LINE_ID));

        self::assertSame(Lines::LINE_ID, $history->lineId);
        self::assertSame(1, $history->version);
        self::assertSame('1000.00', $history->currentValue->toDecimalString());
    }

    public function testReadsALineByItsCanonicalId(): void
    {
        $payroll = new InMemoryPayroll();
        $payroll->bus->dispatch(new RecordCalculatedValue('calculation-1', Lines::LINE_ID, '1000.00', 'USD'));

        $history = ($payroll->getEarningLineHistory)(new GetEarningLineHistory(strtoupper(Lines::LINE_ID)));

        self::assertSame(Lines::LINE_ID, $history->lineId);
    }

    public function testALineWithoutHistoryIsNotFound(): void
    {
        $this->expectException(EarningLineNotFound::class);

        (new InMemoryPayroll()->getEarningLineHistory)(new GetEarningLineHistory(Lines::LINE_ID));
    }

    public function testAMalformedIdIsRefusedBeforeReading(): void
    {
        $this->expectException(InvalidIdentifier::class);

        (new InMemoryPayroll()->getEarningLineHistory)(new GetEarningLineHistory('not-an-id'));
    }
}
