<?php

declare(strict_types=1);

namespace App\Modules\Common\InMemory;

use Common\Application\EventStore;
use Common\Application\Exception\ConcurrencyConflict;
use Common\Domain\DomainEvent;

/** Event store in the memory of the running process. Runs the same contract tests as any other event store. */
final class InMemoryEventStore implements EventStore, InMemoryState
{
    /** @var array<string, list<DomainEvent>> */
    private array $streams = [];

    public function load(string $streamId): array
    {
        return $this->streams[$streamId] ?? [];
    }

    public function append(string $streamId, int $expectedVersion, array $events): void
    {
        if ($events === []) {
            return;
        }

        foreach ($events as $event) {
            if ($event->aggregateId() !== $streamId) {
                throw new \InvalidArgumentException(\sprintf('An event of "%s" cannot be appended to stream "%s".', $event->aggregateId(), $streamId));
            }
        }

        $actualVersion = \count($this->streams[$streamId] ?? []);

        if ($actualVersion !== $expectedVersion) {
            throw ConcurrencyConflict::in($streamId, $expectedVersion, $actualVersion);
        }

        $this->streams[$streamId] = [...$this->streams[$streamId] ?? [], ...$events];
    }

    /** @return array<string, list<DomainEvent>> */
    public function snapshot(): array
    {
        return $this->streams;
    }

    public function restore(mixed $snapshot): void
    {
        \assert(\is_array($snapshot));
        /** @var array<string, list<DomainEvent>> $snapshot */
        $this->streams = $snapshot;
    }
}
