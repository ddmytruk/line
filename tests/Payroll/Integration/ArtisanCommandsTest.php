<?php

declare(strict_types=1);

namespace Tests\Payroll\Integration;

use App\Modules\Payroll\Console\AdjustCommand;
use App\Modules\Payroll\Console\CommandOptions;
use App\Modules\Payroll\Console\HistoryCommand;
use App\Modules\Payroll\Console\RebuildCommand;
use App\Modules\Payroll\Console\RecordValueCommand;
use App\Modules\Payroll\Console\Refusals;
use Illuminate\Contracts\Console\Kernel;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\Common\Integration\TestApplication;
use Tests\Support\Payroll\Lines;

/** The artisan commands of Payroll: a refusal is a message and a failing exit code, not a stack trace. */
#[CoversClass(RecordValueCommand::class)]
#[CoversClass(AdjustCommand::class)]
#[CoversClass(HistoryCommand::class)]
#[CoversClass(RebuildCommand::class)]
#[CoversClass(CommandOptions::class)]
#[CoversClass(Refusals::class)]
final class ArtisanCommandsTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        TestApplication::get();
    }

    protected function setUp(): void
    {
        TestApplication::emptyTables();
    }

    public function testANewCommandIdIsChosenWhenNoneIsGiven(): void
    {
        [$exitCode, $output] = self::artisan('payroll:record-value', ['line' => Lines::LINE_ID, 'amount' => '1050.00', 'currency' => 'USD', '--command-id' => '']);

        self::assertSame(0, $exitCode);
        self::assertMatchesRegularExpression('/\ARecorded \(command id [0-9a-f-]{36}\)\.\z/', $output);
    }

    public function testRecordsAdjustsRebuildsAndShowsTheHistory(): void
    {
        self::assertSame([0, 'Recorded (command id c-1).'], self::artisan('payroll:record-value', ['line' => Lines::LINE_ID, 'amount' => '1050.00', 'currency' => 'USD', '--command-id' => 'c-1']));
        self::assertSame(0, self::artisan('payroll:adjust', self::adjustment(1))[0]);
        self::assertSame(0, self::artisan('payroll:rebuild')[0]);

        [$exitCode, $output] = self::artisan('payroll:history', ['line' => Lines::LINE_ID]);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('version 2', $output);
        self::assertStringContainsString('Adjustment 1           -$45.55', $output);
        self::assertStringContainsString('Current value          $1,004.45', $output);
    }

    public function testARefusalIsAMessageAndAFailingExitCode(): void
    {
        self::artisan('payroll:record-value', ['line' => Lines::LINE_ID, 'amount' => '1050.00', 'currency' => 'USD']);

        self::assertSame([1, 'Stream "' . Lines::LINE_ID . '" is at version 1, but the change was based on version 7.'], self::artisan('payroll:adjust', self::adjustment(7)));
        self::assertSame(1, self::artisan('payroll:history', ['line' => 'not-a-uuid'])[0]);
        self::assertSame([1, 'Earning line 019a7b2c-0000-7000-8000-000000000000 does not exist.'], self::artisan('payroll:history', ['line' => '019a7b2c-0000-7000-8000-000000000000']));
    }

    public function testAnAdjustmentNeedsTheVersionAndTheSpecialist(): void
    {
        $withoutVersion = self::adjustment(1);
        unset($withoutVersion['--expected-version']);

        $withoutSpecialist = self::adjustment(1);
        unset($withoutSpecialist['--specialist']);

        self::assertSame(2, self::artisan('payroll:adjust', $withoutVersion)[0]);
        self::assertSame(2, self::artisan('payroll:adjust', $withoutSpecialist)[0]);
    }

    /** @return array<string, string> */
    private static function adjustment(int $version): array
    {
        return [
            'line' => Lines::LINE_ID,
            'amount' => '-45.55',
            'currency' => 'USD',
            'comment' => 'Dental plan waived',
            '--expected-version' => (string) $version,
            '--specialist' => 'specialist-1',
        ];
    }

    /**
     * @param array<string, string> $parameters
     *
     * @return array{int, string}
     */
    private static function artisan(string $command, array $parameters = []): array
    {
        $kernel = TestApplication::get()->make(Kernel::class);
        $exitCode = $kernel->call($command, $parameters);

        return [$exitCode, trim($kernel->output())];
    }
}
