<?php

declare(strict_types=1);

namespace Tests\Support\Common;

use Common\Application\Clock;

/** A clock that shows the time a test sets. */
final class FixedClock implements Clock
{
    public function __construct(private \DateTimeImmutable $now = new \DateTimeImmutable('2026-01-15 09:00:00', new \DateTimeZone('UTC'))) {}

    public function now(): \DateTimeImmutable
    {
        return $this->now;
    }

    public function set(string $time): void
    {
        $this->now = new \DateTimeImmutable($time, new \DateTimeZone('UTC'));
    }
}
