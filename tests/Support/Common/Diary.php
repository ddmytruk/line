<?php

declare(strict_types=1);

namespace Tests\Support\Common;

use Common\Domain\AggregateRoot;
use Common\Domain\DomainEvent;

/** A minimal aggregate that remembers what happened, for testing the aggregate mechanics. */
final class Diary extends AggregateRoot
{
    private string $id;

    /** @var list<string> */
    public private(set) array $entries = [];

    public static function open(string $id): self
    {
        $diary = new self();
        $diary->recordThat(new SomethingHappened($id, 'opened'));

        return $diary;
    }

    public function write(string $what): void
    {
        $this->recordThat(new SomethingHappened($this->id, $what));
    }

    public function aggregateId(): string
    {
        return $this->id;
    }

    protected function apply(DomainEvent $event): void
    {
        \assert($event instanceof SomethingHappened);
        $this->id = $event->id;
        $this->entries[] = $event->what;
    }
}
