<?php

declare(strict_types=1);

namespace Tests\Support\Common;

use Common\Application\ReadModel\Projector;
use Common\Domain\DomainEvent;

/** A projector that lists what it was given, for testing how projectors are fed. */
final class RecordingProjector implements Projector
{
    /** @var list<string> */
    public private(set) array $seen = [];

    public function project(DomainEvent $event): void
    {
        $this->seen[] = $event instanceof SomethingHappened ? $event->what : $event::class;
    }

    public function reset(): void
    {
        $this->seen = [];
    }
}
