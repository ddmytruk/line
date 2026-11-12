<?php

declare(strict_types=1);

namespace Tests\Payroll\Cli;

use App\Modules\Payroll\Cli\DemoCommand;
use App\Modules\Payroll\Cli\HistoryView;
use App\Modules\Payroll\Cli\MoneyFormat;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DemoCommand::class)]
#[CoversClass(HistoryView::class)]
#[CoversClass(MoneyFormat::class)]
final class DemoCommandTest extends TestCase
{
    public function testPrintsTheHistoryOfTheReferenceScenario(): void
    {
        $output = new DemoCommand(new \DateTimeImmutable('2026-01-15 09:00:00', new \DateTimeZone('UTC')))->run();

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
            TEXT, $output);
    }

    public function testTheFullLogKeepsTheFirstValueAndTheIgnoredRecalculation(): void
    {
        $output = new DemoCommand(new \DateTimeImmutable('2026-01-15 09:00:00', new \DateTimeZone('UTC')))->run();

        self::assertStringContainsString('2026-01-15 09:00:00  Calculated                           $1,000.00', $output);
        self::assertStringContainsString('2026-01-15 12:00:00  Recalculation ignored (line frozen)  $1,100.00', $output);
        self::assertStringContainsString('4. Recalculated $1,100.00 (ignored: the line is frozen) -> current value $1,004.45', $output);
    }
}
