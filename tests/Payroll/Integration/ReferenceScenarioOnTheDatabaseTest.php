<?php

declare(strict_types=1);

namespace Tests\Payroll\Integration;

use App\Modules\Common\Clock\SystemClock;
use App\Modules\Common\Database\DatabaseEventStore;
use App\Modules\Payroll\InMemory\InMemoryPayrollModule;
use Common\Application\Clock;
use Common\Application\Command\Command;
use Common\Application\Command\CommandBus;
use Common\Application\EventStore;
use Common\Application\ReadModel\ReadModelRebuild;
use Payroll\Application\Command\AddManualAdjustment;
use Payroll\Application\Command\RecordCalculatedValue;
use Payroll\Application\History\EarningLineHistories;
use Payroll\Application\History\EarningLineHistory;
use Payroll\Application\Query\GetEarningLineHistory;
use Payroll\Application\Query\GetEarningLineHistoryHandler;
use PHPUnit\Framework\TestCase;
use Tests\Common\Integration\TestApplication;
use Tests\Support\Common\FixedClock;
use Tests\Support\Payroll\Lines;

/** The Payroll module wired on MySQL gives the same history as in memory, and builds it again from the stored events. */
final class ReferenceScenarioOnTheDatabaseTest extends TestCase
{
    private const array STEPS = [
        ['calculate', '1000.00', ''],
        ['calculate', '1050.00', ''],
        ['adjust', '-45.55', 'Dental plan waived: benefit amount removed'],
        ['calculate', '1100.00', ''],
        ['adjust', '+100.10', 'Approved overtime missing from the source data'],
        ['adjust', '-0.10', 'Rounding'],
        ['adjust', '-0.20', 'Rounding, second pass'],
        ['adjust', '+0.20', 'Reverts adjustment #4, entered by mistake'],
    ];

    private FixedClock $clock;

    public static function setUpBeforeClass(): void
    {
        TestApplication::get();
    }

    protected function setUp(): void
    {
        TestApplication::emptyTables();
        $this->clock = new FixedClock();
        TestApplication::get()->instance(Clock::class, $this->clock);
    }

    protected function tearDown(): void
    {
        TestApplication::get()->instance(Clock::class, new SystemClock());
    }

    public function testTheHistoryOnTheDatabaseEqualsTheOneInMemory(): void
    {
        $app = TestApplication::get();
        $inMemory = new InMemoryPayrollModule($this->clock);
        $onDatabase = $app->make(CommandBus::class);

        foreach (self::STEPS as $index => [$action, $amount, $comment]) {
            $this->clock->set(\sprintf('2026-01-15 %02d:00:00', 9 + $index));
            $version = $index === 0 ? 0 : self::history()->version;
            $command = self::command($index, $action, $amount, $comment, $version);
            $inMemory->bus->dispatch($command);
            $onDatabase->dispatch($command);
        }

        $expected = ($inMemory->getEarningLineHistory)(new GetEarningLineHistory(Lines::LINE_ID));
        self::assertEquals($expected, self::history());
        self::assertSame('1104.45', self::history()->currentValue->toDecimalString());
        self::assertSame(8, self::history()->version);
        self::assertInstanceOf(DatabaseEventStore::class, $app->make(EventStore::class));
    }

    public function testARebuildFromTheStoredEventsGivesTheSameHistory(): void
    {
        $bus = TestApplication::get()->make(CommandBus::class);

        foreach (self::STEPS as $index => [$action, $amount, $comment]) {
            $bus->dispatch(self::command($index, $action, $amount, $comment, $index === 0 ? 0 : self::history()->version));
        }

        $built = self::history();
        TestApplication::get()->make(EarningLineHistories::class)->clear();
        TestApplication::get()->make(ReadModelRebuild::class)->run();

        self::assertEquals($built, self::history());
    }

    private static function command(int $index, string $action, string $amount, string $comment, int $version): Command
    {
        $commandId = 'step-' . ($index + 1);

        return $action === 'calculate'
            ? new RecordCalculatedValue($commandId, Lines::LINE_ID, $amount, 'USD')
            : new AddManualAdjustment($commandId, Lines::LINE_ID, $version, $amount, 'USD', $comment, 'payroll-specialist');
    }

    private static function history(): EarningLineHistory
    {
        return TestApplication::get()->make(GetEarningLineHistoryHandler::class)(new GetEarningLineHistory(Lines::LINE_ID));
    }
}
