<?php

declare(strict_types=1);

namespace Tests\Payroll\Application;

use App\Modules\Common\InMemory\InMemoryEventStore;
use Common\Application\Exception\ConcurrencyConflict;
use Common\Domain\DomainEvent;
use Payroll\Application\Command\EarningLines;
use Payroll\Application\Exception\CorruptedEarningLineEvents;
use Payroll\Application\Exception\EarningLineNotFound;
use Payroll\Domain\Currency;
use Payroll\Domain\EarningLineId;
use Payroll\Domain\Event\SystemValueCalculated;
use Payroll\Domain\Event\SystemValueRecalculated;
use Payroll\Domain\Money;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Support\Payroll\Lines;

/** A saved line comes back with the same state, and a change based on an outdated line is refused. */
#[CoversClass(EarningLines::class)]
final class EarningLinesTest extends TestCase
{
    private InMemoryEventStore $store;

    private EarningLines $lines;

    protected function setUp(): void
    {
        $this->store = new InMemoryEventStore();
        $this->lines = new EarningLines($this->store);
    }

    public function testALoadedLineHasTheStateOfTheSavedOne(): void
    {
        $line = Lines::calculated('1050.00');
        Lines::adjust($line, '-45.55');
        $line->recordSystemValue(Lines::usd('1100.00'), Lines::at());
        $this->lines->save($line);

        $loaded = $this->lines->get(self::id());

        self::assertSame('1004.45', $loaded->currentValue()->toDecimalString());
        self::assertSame('1050.00', $loaded->systemValue()->toDecimalString());
        self::assertTrue($loaded->isFrozen());
        self::assertSame(3, $loaded->version());
        self::assertSame([], $loaded->releaseEvents());
    }

    public function testSavesOnlyWhatTheLineRecordedSinceItWasLoaded(): void
    {
        $this->lines->save(Lines::calculated('1000.00'));

        $line = $this->lines->get(self::id());
        Lines::adjust($line, '+5.00');
        $this->lines->save($line);
        $this->lines->save($line);

        self::assertCount(2, $this->store->load(Lines::LINE_ID));
        self::assertSame('1005.00', $this->lines->get(self::id())->currentValue()->toDecimalString());
    }

    public function testRefusesAChangeMadeOnALineThatChangedAfterItWasLoaded(): void
    {
        $this->lines->save(Lines::calculated('1000.00'));
        $anna = $this->lines->get(self::id());
        $boris = $this->lines->get(self::id());

        Lines::adjust($anna, '-45.55', 'Benefit no longer applies');
        $this->lines->save($anna);
        Lines::adjust($boris, '-45.55', 'Benefit no longer applies');

        try {
            $this->lines->save($boris);
            self::fail('A change based on an outdated line must be refused.');
        } catch (ConcurrencyConflict) {
        }

        $stored = $this->lines->get(self::id());
        self::assertCount(1, $stored->adjustments());
        self::assertSame('954.45', $stored->currentValue()->toDecimalString());
    }

    public function testALineWhoseSaveFailedCannotBeSavedAgain(): void
    {
        $this->lines->save(Lines::calculated('1000.00'));
        $anna = $this->lines->get(self::id());
        $boris = $this->lines->get(self::id());
        Lines::adjust($anna, '-45.55');
        $this->lines->save($anna);
        Lines::adjust($boris, '+100.00');

        try {
            $this->lines->save($boris);
            self::fail('A change based on an outdated line must be refused.');
        } catch (ConcurrencyConflict) {
        }

        try {
            $this->lines->save($boris);
            self::fail('Saving the same line object again must not report success.');
        } catch (\LogicException) {
        }

        self::assertSame('954.45', $this->lines->get(self::id())->currentValue()->toDecimalString());
    }

    public function testRefusesToCreateALineThatAlreadyExists(): void
    {
        $this->lines->save(Lines::calculated('1000.00'));

        $this->expectException(ConcurrencyConflict::class);
        $this->lines->save(Lines::calculated('1200.00'));
    }

    public function testALineThatWasNeverSavedIsNotFound(): void
    {
        self::assertNull($this->lines->find(self::id()));

        $this->expectException(EarningLineNotFound::class);
        $this->lines->get(self::id());
    }

    /** @return iterable<string, array{list<DomainEvent>}> */
    public static function historiesThatBreakTheRules(): iterable
    {
        $id = self::id();

        yield 'broken order of facts' => [[new SystemValueRecalculated($id, Lines::usd('1000.00'), Lines::at())]];
        yield 'a value that breaks a rule of money' => [[
            new SystemValueCalculated($id, Lines::usd('1000.00'), Lines::at()),
            new SystemValueRecalculated($id, Money::fromDecimal('1000.00', Currency::EUR), Lines::at()),
        ]];
    }

    /** @param list<DomainEvent> $history */
    #[DataProvider('historiesThatBreakTheRules')]
    public function testReportsAStoredHistoryThatBreaksTheRulesAsCorrupted(array $history): void
    {
        $this->store->append(Lines::LINE_ID, 0, $history);

        $this->expectException(CorruptedEarningLineEvents::class);
        $this->lines->get(self::id());
    }

    private static function id(): EarningLineId
    {
        return new EarningLineId(Lines::LINE_ID);
    }
}
