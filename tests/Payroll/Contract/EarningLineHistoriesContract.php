<?php

declare(strict_types=1);

namespace Tests\Payroll\Contract;

use Payroll\Application\History\EarningLineHistories;
use Payroll\Application\History\EarningLineHistory;
use PHPUnit\Framework\TestCase;
use Tests\Support\Payroll\Lines;

/** What every store of line histories must guarantee. Each implementation runs these tests by extending this class. */
abstract class EarningLineHistoriesContract extends TestCase
{
    abstract protected function createStore(): EarningLineHistories;

    public function testAnUnknownLineHasNoHistory(): void
    {
        self::assertNull($this->createStore()->find(Lines::LINE_ID));
    }

    public function testReturnsTheSavedHistoryWithEveryDetail(): void
    {
        $store = $this->createStore();
        $history = self::history(Lines::LINE_ID, '1000.00');
        $store->save($history);

        self::assertEquals($history, $store->find(Lines::LINE_ID));
    }

    public function testASavedHistoryReplacesThePreviousOneOfTheSameLine(): void
    {
        $store = $this->createStore();
        $store->save(self::history(Lines::LINE_ID, '1000.00'));
        $store->save(self::history(Lines::LINE_ID, '1050.00'));

        self::assertSame('1050.00', $store->find(Lines::LINE_ID)?->currentValue->toDecimalString());
    }

    public function testClearForgetsEveryHistory(): void
    {
        $store = $this->createStore();
        $store->save(self::history(Lines::LINE_ID, '1000.00'));
        $store->save(self::history('019a7b2c-0000-7000-8000-000000000000', '10.00'));
        $store->clear();

        self::assertNull($store->find(Lines::LINE_ID));
        self::assertNull($store->find('019a7b2c-0000-7000-8000-000000000000'));
    }

    private static function history(string $lineId, string $value): EarningLineHistory
    {
        return EarningLineHistory::start($lineId, Lines::at(), Lines::usd($value));
    }
}
