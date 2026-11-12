<?php

declare(strict_types=1);

namespace Common\Application\ReadModel;

use Common\Domain\DomainEvent;

/** Builds a model for reading from events. Ignores events it does not use. */
interface Projector
{
    public function project(DomainEvent $event): void;

    /** Forgets everything built so far, before a rebuild. */
    public function reset(): void;
}
