<?php

declare(strict_types=1);

namespace App\Modules\Common\InMemory;

use Common\Application\Transaction;

/**
 * Takes a snapshot of the in-memory storages before the work and puts them back if the work fails.
 * Used inside another run, it behaves like a savepoint: only the inner work is undone.
 */
final readonly class InMemoryTransaction implements Transaction
{
    /** @param list<InMemoryState> $states */
    public function __construct(private array $states) {}

    public function run(callable $work): mixed
    {
        $snapshots = array_map(static fn(InMemoryState $state): mixed => $state->snapshot(), $this->states);

        try {
            return $work();
        } catch (\Throwable $failure) {
            foreach ($this->states as $i => $state) {
                $state->restore($snapshots[$i]);
            }

            throw $failure;
        }
    }
}
