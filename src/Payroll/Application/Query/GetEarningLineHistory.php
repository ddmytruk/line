<?php

declare(strict_types=1);

namespace Payroll\Application\Query;

/** A request to read the history of one line: its current value, version, drift and every fact. */
final readonly class GetEarningLineHistory
{
    public function __construct(public string $lineId) {}
}
