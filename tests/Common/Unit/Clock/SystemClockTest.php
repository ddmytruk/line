<?php

declare(strict_types=1);

namespace Tests\Common\Unit\Clock;

use App\Modules\Common\Clock\SystemClock;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/** Stored facts get the current time in UTC, whatever the time zone of the server. */
#[CoversClass(SystemClock::class)]
final class SystemClockTest extends TestCase
{
    public function testTellsTheCurrentTimeInUtc(): void
    {
        $before = time();
        $now = new SystemClock()->now();

        self::assertSame('UTC', $now->getTimezone()->getName());
        self::assertGreaterThanOrEqual($before, $now->getTimestamp());
        self::assertLessThanOrEqual(time(), $now->getTimestamp());
    }
}
