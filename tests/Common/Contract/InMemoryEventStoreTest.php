<?php

declare(strict_types=1);

namespace Tests\Common\Contract;

use App\Modules\Common\InMemory\InMemoryEventStore;
use Common\Application\EventStore;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(InMemoryEventStore::class)]
final class InMemoryEventStoreTest extends EventStoreContract
{
    protected function createStore(): EventStore
    {
        return new InMemoryEventStore();
    }
}
