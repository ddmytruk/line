<?php

declare(strict_types=1);

namespace App\Modules\Payroll\InMemory;

use App\Modules\Common\InMemory\InMemoryState;
use Payroll\Application\History\EarningLineHistories;
use Payroll\Application\History\EarningLineHistory;

/** Line histories in the memory of the running process. */
final class InMemoryEarningLineHistories implements EarningLineHistories, InMemoryState
{
    /** @var array<string, EarningLineHistory> */
    private array $histories = [];

    public function find(string $lineId): ?EarningLineHistory
    {
        return $this->histories[$lineId] ?? null;
    }

    public function save(EarningLineHistory $history): void
    {
        $this->histories[$history->lineId] = $history;
    }

    public function clear(): void
    {
        $this->histories = [];
    }

    /** @return array<string, EarningLineHistory> */
    public function snapshot(): array
    {
        return $this->histories;
    }

    public function restore(mixed $snapshot): void
    {
        \assert(\is_array($snapshot));
        /** @var array<string, EarningLineHistory> $snapshot */
        $this->histories = $snapshot;
    }
}
