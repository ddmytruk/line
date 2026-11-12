<?php

declare(strict_types=1);

namespace Tests\Payroll\Integration;

use App\Modules\Common\Clock\SystemClock;
use App\Modules\Payroll\Http\AdjustController;
use App\Modules\Payroll\Http\HistoryController;
use App\Modules\Payroll\Http\JsonBody;
use App\Modules\Payroll\Http\RecordValueController;
use App\Modules\Payroll\Http\Responses;
use Common\Application\Clock;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Tests\Common\Integration\TestApplication;
use Tests\Support\Common\FixedClock;
use Tests\Support\Payroll\Lines;

/** The HTTP API of Payroll: commands answer 204 or a refusal with its status; the history is JSON. */
#[CoversClass(RecordValueController::class)]
#[CoversClass(AdjustController::class)]
#[CoversClass(HistoryController::class)]
#[CoversClass(JsonBody::class)]
#[CoversClass(Responses::class)]
final class HttpApiTest extends TestCase
{
    private const string LINE = '/lines/' . Lines::LINE_ID;

    public static function setUpBeforeClass(): void
    {
        TestApplication::get();
    }

    protected function setUp(): void
    {
        TestApplication::emptyTables();
        TestApplication::get()->instance(Clock::class, new FixedClock());
    }

    protected function tearDown(): void
    {
        TestApplication::get()->instance(Clock::class, new SystemClock());
    }

    public function testRecordsAdjustsAndShowsTheHistory(): void
    {
        self::assertSame(204, self::calculate('c-1', '1050.00')->getStatusCode());
        self::assertSame(204, self::adjust('a-1', 1, '-45.55')->getStatusCode());
        self::assertSame(204, self::calculate('c-2', '1100.00')->getStatusCode());

        $history = self::send('GET', self::LINE . '/history');

        self::assertSame(200, $history->getStatusCode());
        self::assertSame([
            'lineId' => Lines::LINE_ID,
            'version' => 3,
            'frozen' => true,
            'systemValue' => ['amount' => '1050.00', 'currency' => 'USD'],
            'currentValue' => ['amount' => '1004.45', 'currency' => 'USD'],
            'lastCalculatedValue' => ['amount' => '1100.00', 'currency' => 'USD'],
            'drift' => ['amount' => '50.00', 'currency' => 'USD'],
            'entries' => [
                ['kind' => 'calculated', 'at' => '2026-01-15T09:00:00.000000+00:00', 'amount' => ['amount' => '1050.00', 'currency' => 'USD'], 'currentValueAfter' => ['amount' => '1050.00', 'currency' => 'USD']],
                [
                    'kind' => 'adjusted',
                    'at' => '2026-01-15T09:00:00.000000+00:00',
                    'amount' => ['amount' => '-45.55', 'currency' => 'USD'],
                    'currentValueAfter' => ['amount' => '1004.45', 'currency' => 'USD'],
                    'adjustmentNumber' => 1,
                    'comment' => 'Correction',
                    'specialistId' => 'specialist-1',
                ],
                [
                    'kind' => 'recalculation ignored',
                    'at' => '2026-01-15T09:00:00.000000+00:00',
                    'amount' => ['amount' => '1100.00', 'currency' => 'USD'],
                    'currentValueAfter' => ['amount' => '1004.45', 'currency' => 'USD'],
                    'drift' => ['amount' => '50.00', 'currency' => 'USD'],
                ],
            ],
        ], json_decode((string) $history->getContent(), true, flags: \JSON_THROW_ON_ERROR));
    }

    public function testARepeatedRequestChangesNothing(): void
    {
        self::calculate('c-1', '1050.00');
        self::adjust('a-1', 1, '-45.55');

        self::assertSame(204, self::adjust('a-1', 1, '-45.55')->getStatusCode());
        self::assertSame(2, self::version());
    }

    public function testAnAdjustmentOfAVersionTheSpecialistNoLongerSeesIsAConflict(): void
    {
        self::calculate('c-1', '1050.00');
        self::calculate('c-2', '1060.00');

        self::assertSame(409, self::adjust('a-1', 1, '-45.55')->getStatusCode());
        self::assertSame(2, self::version());
    }

    public function testAnIdReusedForOtherContentIsAConflict(): void
    {
        self::calculate('c-1', '1050.00');

        self::assertSame(409, self::calculate('c-1', '1060.00')->getStatusCode());
    }

    /** @return iterable<string, array{array<string, mixed>}> */
    public static function invalidAdjustments(): iterable
    {
        $valid = ['commandId' => 'a-1', 'expectedVersion' => 1, 'amount' => '-45.55', 'currency' => 'USD', 'comment' => 'Correction', 'specialistId' => 'specialist-1'];

        yield 'amount as a JSON number' => [['amount' => -45.55] + $valid];
        yield 'version as text' => [['expectedVersion' => '1'] + $valid];
        yield 'missing comment' => [array_diff_key($valid, ['comment' => true])];
        yield 'zero amount' => [['amount' => '0.00'] + $valid];
        yield 'more than cents' => [['amount' => '-45.555'] + $valid];
        yield 'unknown currency' => [['currency' => 'XXX'] + $valid];
        yield 'other currency than the line' => [['currency' => 'EUR'] + $valid];
        yield 'empty command id' => [['commandId' => ''] + $valid];
        yield 'negative version' => [['expectedVersion' => -1] + $valid];
    }

    /** @param array<string, mixed> $body */
    #[DataProvider('invalidAdjustments')]
    public function testAnAdjustmentThatBreaksARuleIsRefusedAndChangesNothing(array $body): void
    {
        self::calculate('c-1', '1050.00');

        $response = self::send('POST', self::LINE . '/adjustments', $body);

        self::assertSame(422, $response->getStatusCode(), (string) $response->getContent());
        self::assertSame(1, self::version());
    }

    public function testABodyThatIsNotJsonIsRefused(): void
    {
        $request = Request::create(self::LINE . '/calculations', 'POST', ['commandId' => 'c-1', 'amount' => '1050.00', 'currency' => 'USD']);

        self::assertSame(422, TestApplication::get()->make(Kernel::class)->handle($request)->getStatusCode());
    }

    public function testDamagedStoredDataIsAFaultOfTheServerNotOfTheRequest(): void
    {
        TestApplication::connection()->table('events')->insert([
            'stream_id' => Lines::LINE_ID,
            'stream_version' => 1,
            'event_type' => 'payroll.system_value_calculated',
            'payload' => json_encode(['lineId' => Lines::LINE_ID, 'value' => ['amount' => '10.005', 'currency' => 'USD'], 'occurredAt' => '2026-01-15T09:00:00.000000Z'], \JSON_THROW_ON_ERROR),
        ]);

        $response = self::calculate('c-1', '1050.00');

        self::assertSame(500, $response->getStatusCode());
        self::assertSame(['message' => 'Server Error'], json_decode((string) $response->getContent(), true, flags: \JSON_THROW_ON_ERROR));
    }

    public function testALineThatDoesNotExistIsNotFound(): void
    {
        self::assertSame(404, self::send('GET', self::LINE . '/history')->getStatusCode());
        self::assertSame(404, self::adjust('a-1', 1, '-45.55')->getStatusCode());
    }

    private static function calculate(string $commandId, string $amount): Response
    {
        return self::send('POST', self::LINE . '/calculations', ['commandId' => $commandId, 'amount' => $amount, 'currency' => 'USD']);
    }

    private static function adjust(string $commandId, int $version, string $amount): Response
    {
        return self::send('POST', self::LINE . '/adjustments', [
            'commandId' => $commandId,
            'expectedVersion' => $version,
            'amount' => $amount,
            'currency' => 'USD',
            'comment' => 'Correction',
            'specialistId' => 'specialist-1',
        ]);
    }

    private static function version(): int
    {
        $history = json_decode((string) self::send('GET', self::LINE . '/history')->getContent(), true, flags: \JSON_THROW_ON_ERROR);
        \assert(\is_array($history) && \is_int($history['version']));

        return $history['version'];
    }

    /** @param array<string, mixed> $body */
    private static function send(string $method, string $uri, array $body = []): Response
    {
        $request = Request::create($uri, $method, server: ['CONTENT_TYPE' => 'application/json'], content: json_encode($body, \JSON_THROW_ON_ERROR));

        return TestApplication::get()->make(Kernel::class)->handle($request);
    }
}
