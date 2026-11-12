<?php

declare(strict_types=1);

namespace Payroll\Application\History;

/** Where the histories of lines are kept for reading. Only the projector writes here. */
interface EarningLineHistories
{
    public function find(string $lineId): ?EarningLineHistory;

    public function save(EarningLineHistory $history): void;

    public function clear(): void;
}
