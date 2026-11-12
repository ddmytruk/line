<?php

declare(strict_types=1);

namespace Tests\Common\Contract;

use App\Modules\Common\InMemory\InMemoryEventStore;
use App\Modules\Common\InMemory\InMemoryProcessedCommands;
use App\Modules\Common\InMemory\InMemoryTransaction;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(InMemoryTransaction::class)]
final class InMemoryTransactionTest extends TransactionContract
{
    protected function createTransactionAndStores(): array
    {
        $store = new InMemoryEventStore();
        $processed = new InMemoryProcessedCommands();

        return [new InMemoryTransaction([$store, $processed]), $store, $processed];
    }
}
