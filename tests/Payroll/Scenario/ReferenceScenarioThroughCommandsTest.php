<?php

declare(strict_types=1);

namespace Tests\Payroll\Scenario;

use Common\Application\Command\CommandBus;
use Payroll\Application\Command\AddManualAdjustment;
use Payroll\Application\Command\RecordCalculatedValue;
use Payroll\Domain\ManualAdjustment;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\Support\Payroll\InMemoryPayroll;
use Tests\Support\Payroll\Lines;

/**
 * The reference scenario of docs/payroll/domain.md sent as commands through the command bus,
 * the way the calculation and a specialist change a stored line. Every intermediate value is read from the store.
 */
#[CoversClass(CommandBus::class)]
final class ReferenceScenarioThroughCommandsTest extends TestCase
{
    private InMemoryPayroll $payroll;

    private int $commands = 0;

    protected function setUp(): void
    {
        $this->payroll = new InMemoryPayroll();
    }

    public function testEveryStepAndTheFinalHistory(): void
    {
        $this->calculate('1000.00');
        $this->assertValue('1000.00', 'step 1');

        $this->calculate('1050.00');
        $this->assertValue('1050.00', 'step 2: the open line takes the recalculated value');

        $this->adjust('-45.55', 'Dental plan waived: benefit amount removed');
        $this->assertValue('1004.45', 'step 3: adjustment #1 freezes the line');

        $this->calculate('1100.00');
        $this->assertValue('1004.45', 'step 4: the frozen line ignores the recalculation');

        $this->adjust('+100.10', 'Approved overtime missing from the source data');
        $this->assertValue('1104.55', 'step 5');

        $this->adjust('-0.10', 'Rounding');
        $this->assertValue('1104.45', 'step 6');

        $this->adjust('-0.20', 'Rounding, second pass');
        $this->assertValue('1104.25', 'step 7');

        $this->adjust('+0.20', 'Reverts adjustment #4, entered by mistake');
        $this->assertValue('1104.45', 'step 8: adjustment #5 compensates #4');

        $line = $this->payroll->line();
        self::assertSame('1050.00', $line->systemValue()->toDecimalString(), 'frozen system value');
        self::assertSame(
            [[1, '-45.55'], [2, '100.10'], [3, '-0.10'], [4, '-0.20'], [5, '0.20']],
            array_map(
                static fn(ManualAdjustment $adjustment): array => [$adjustment->number, $adjustment->amount->toDecimalString()],
                $line->adjustments(),
            ),
        );
        self::assertSame(8, $line->version(), 'every step recorded exactly one fact');
    }

    private function calculate(string $amount): void
    {
        $this->payroll->bus->dispatch(new RecordCalculatedValue($this->nextId(), Lines::LINE_ID, $amount, 'USD'));
    }

    private function adjust(string $amount, string $comment): void
    {
        $this->payroll->bus->dispatch(new AddManualAdjustment(
            $this->nextId(),
            Lines::LINE_ID,
            $this->payroll->versionOf(),
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

    private function assertValue(string $expected, string $step): void
    {
        self::assertSame($expected, $this->payroll->line()->currentValue()->toDecimalString(), $step);
    }
}
