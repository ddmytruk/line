<?php

declare(strict_types=1);

namespace Tests\Support\Common;

use App\Modules\Common\InMemory\InMemoryEventStore;
use App\Modules\Common\InMemory\InMemoryState;
use Common\Application\EventStore;

/**
 * Lets another writer append first: before the next append, it runs the given write on the store.
 * Simulates another process that loaded the same stream at the same time and committed first,
 * so a rolled back transaction of this process keeps what the other process wrote.
 */
final class InterleavingEventStore implements EventStore, InMemoryState
{
    /** @var list<\Closure(EventStore): void> */
    private array $pending = [];

    /** @var list<\Closure(EventStore): void> */
    private array $committedByOthers = [];

    public function __construct(private readonly InMemoryEventStore $store) {}

    /** @param \Closure(EventStore): void $write */
    public function beforeNextAppend(\Closure $write): void
    {
        $this->pending[] = $write;
    }

    public function load(string $streamId): array
    {
        return $this->store->load($streamId);
    }

    public function all(): iterable
    {
        return $this->store->all();
    }

    public function append(string $streamId, int $expectedVersion, array $events): void
    {
        $write = array_shift($this->pending);

        if ($write !== null) {
            $write($this->store);
            $this->committedByOthers[] = $write;
        }

        $this->store->append($streamId, $expectedVersion, $events);
    }

    /** @return array{mixed, int} */
    public function snapshot(): array
    {
        return [$this->store->snapshot(), \count($this->committedByOthers)];
    }

    public function restore(mixed $snapshot): void
    {
        \assert(\is_array($snapshot) && \is_int($snapshot[1]));
        $this->store->restore($snapshot[0]);

        foreach (\array_slice($this->committedByOthers, $snapshot[1]) as $write) {
            $write($this->store);
        }
    }
}
