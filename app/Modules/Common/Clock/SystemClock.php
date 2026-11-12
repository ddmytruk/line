<?php

declare(strict_types=1);

namespace App\Modules\Common\Clock;

use Common\Application\Clock;

/** The time of the machine, in UTC, so stored facts do not depend on the time zone of a server. */
final readonly class SystemClock implements Clock
{
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }
}
