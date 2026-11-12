<?php

declare(strict_types=1);

namespace Common\Application;

use Common\Application\Exception\ConcurrencyConflict;
use Common\Domain\DomainEvent;

/**
 * Keeps the events of each aggregate in a stream, in the order they were appended. Events are never changed or removed.
 *
 * The version of a stream is the number of events in it. A writer states the version it based its decision on,
 * so a decision made on an outdated state is refused instead of being written over a newer one (ADR 0008).
 */
interface EventStore
{
    /** @return list<DomainEvent> the stream in the order of appending; empty for a stream that does not exist */
    public function load(string $streamId): array;

    /**
     * Appends all events or none.
     *
     * @param list<DomainEvent> $events events of the aggregate with id $streamId; an empty list changes nothing and checks nothing
     *
     * @throws ConcurrencyConflict       if the stream does not hold exactly $expectedVersion events
     * @throws \InvalidArgumentException if an event belongs to another aggregate
     */
    public function append(string $streamId, int $expectedVersion, array $events): void;
}
