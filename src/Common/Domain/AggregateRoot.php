<?php

declare(strict_types=1);

namespace Common\Domain;

/**
 * An aggregate whose state changes only by applying events (ADR 0005).
 *
 * The same apply() runs for a new event (recordThat) and for replaying the history (reconstitute),
 * so an aggregate rebuilt from its events always has the state it had when the events were recorded.
 */
abstract class AggregateRoot
{
    /** Number of events applied so far: replayed and newly recorded. */
    private int $version = 0;

    /** @var list<DomainEvent> */
    private array $pendingEvents = [];

    final protected function __construct() {}

    /**
     * Rebuilds the aggregate from its history.
     *
     * @param iterable<DomainEvent> $history
     *
     * @throws \UnexpectedValueException if the history is empty or holds events of another aggregate
     */
    public static function reconstitute(iterable $history): static
    {
        $aggregate = new static();
        $aggregateId = null;

        foreach ($history as $event) {
            $aggregateId ??= $event->aggregateId();

            if ($event->aggregateId() !== $aggregateId) {
                throw new \UnexpectedValueException(\sprintf('An event of "%s" is in the history of "%s".', $event->aggregateId(), $aggregateId));
            }

            $aggregate->applyAndCount($event);
        }

        if ($aggregateId === null) {
            throw new \UnexpectedValueException('An aggregate cannot be rebuilt from an empty history.');
        }

        return $aggregate;
    }

    abstract public function aggregateId(): string;

    public function version(): int
    {
        return $this->version;
    }

    /**
     * Hands over the events recorded since the last call and forgets them.
     *
     * @return list<DomainEvent>
     */
    public function releaseEvents(): array
    {
        $events = $this->pendingEvents;
        $this->pendingEvents = [];

        return $events;
    }

    /** Changes the state according to the event. Must not decide anything: the decision was made before recording. */
    abstract protected function apply(DomainEvent $event): void;

    protected function recordThat(DomainEvent $event): void
    {
        $this->applyAndCount($event);
        $this->pendingEvents[] = $event;
    }

    private function applyAndCount(DomainEvent $event): void
    {
        $this->apply($event);
        ++$this->version;
    }
}
