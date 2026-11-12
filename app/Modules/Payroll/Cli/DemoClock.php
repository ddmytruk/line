<?php

declare(strict_types=1);

namespace App\Modules\Payroll\Cli;

use Common\Application\Clock;

/** The clock of the demo: starts at a given time and moves one hour forward for every step of the scenario. */
final class DemoClock implements Clock
{
    public function __construct(private \DateTimeImmutable $now) {}

    public function now(): \DateTimeImmutable
    {
        return $this->now;
    }

    public function nextStep(): void
    {
        $this->now = $this->now->modify('+1 hour');
    }
}
