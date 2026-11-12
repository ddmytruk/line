<?php

declare(strict_types=1);

namespace App\Modules\Common\InMemory;

/** In-memory storage that an InMemoryTransaction can put back to an earlier state. */
interface InMemoryState
{
    public function snapshot(): mixed;

    public function restore(mixed $snapshot): void;
}
