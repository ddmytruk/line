<?php

declare(strict_types=1);

namespace Tests\Payroll\Cli;

use App\Modules\Payroll\Cli\DemoClock;
use App\Modules\Payroll\Cli\DemoCommand;
use App\Modules\Payroll\Cli\HistoryView;
use App\Modules\Payroll\Cli\MoneyFormat;
use App\Modules\Payroll\InMemory\InMemoryPayrollModule;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DemoCommand::class)]
#[CoversClass(DemoClock::class)]
#[CoversClass(HistoryView::class)]
#[CoversClass(MoneyFormat::class)]
#[CoversClass(InMemoryPayrollModule::class)]
final class DemoCommandTest extends TestCase
{
    private string $output;

    protected function setUp(): void
    {
        $this->output = new DemoCommand(new \DateTimeImmutable('2026-01-15 09:00:00', new \DateTimeZone('UTC')))->run();
    }

    public function testPrintsTheHistoryOfTheReferenceScenarioWithItsDrift(): void
    {
        self::assertStringContainsString(<<<'TEXT'
            History
            Entry                  Value      Comment
            ---------------------  ---------  ----------------------------------------------
            System value (frozen)  $1,050.00
            Adjustment 1           -$45.55    Dental plan waived: benefit amount removed
            Adjustment 2           +$100.10   Approved overtime missing from the source data
            Adjustment 3           -$0.10     Rounding
            Adjustment 4           -$0.20     Rounding, second pass
            Adjustment 5           +$0.20     Reverts adjustment #4, entered by mistake
            Current value          $1,104.45
            Drift                  +$50.00    latest calculation $1,100.00, not applied
            TEXT, $this->output);
    }

    public function testTheFullLogKeepsEveryFactWithTheValueAfterIt(): void
    {
        self::assertStringContainsString('2026-01-15 09:00:00  Calculated                            $1,000.00  $1,000.00', $this->output);
        self::assertStringContainsString('2026-01-15 12:00:00  Recalculation ignored, drift +$50.00  $1,100.00  $1,004.45', $this->output);
        self::assertStringContainsString('4. Recalculated $1,100.00 (ignored: the line is frozen) -> current value $1,004.45', $this->output);
    }

    public function testShowsWhatTheCommandBusProtectsAgainst(): void
    {
        self::assertStringContainsString(<<<'TEXT'
            Protection
            - Step 5 sent again under the same command id: nothing changed, the line stays at version 8.
            - A second specialist who still saw version 7 adjusts the line: refused, the line changed since.
            - The history is dropped and rebuilt from all stored events: it equals the one built along the way.
            TEXT, $this->output);
    }
}
