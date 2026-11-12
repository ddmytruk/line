<?php

declare(strict_types=1);

namespace Common\Application\ReadModel;

use Common\Application\EventStore;

/**
 * Hands every appended event to the projectors right after the append, in the same transaction (ADR 0010).
 * If a projector fails, the transaction drops the events too, so the models for reading never fall behind the events.
 */
final readonly class ProjectingEventStore implements EventStore
{
    /** @param list<Projector> $projectors */
    public function __construct(
        private EventStore $store,
        private array $projectors,
    ) {}

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
        $this->store->append($streamId, $expectedVersion, $events);

        foreach ($events as $event) {
            foreach ($this->projectors as $projector) {
                $projector->project($event);
            }
        }
    }
}
