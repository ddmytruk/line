<?php

declare(strict_types=1);

namespace Common\Application;

/** The current time, as a port, so that handlers can be tested at a known time. */
interface Clock
{
    public function now(): \DateTimeImmutable;
}
