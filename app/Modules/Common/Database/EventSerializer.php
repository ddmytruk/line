<?php

declare(strict_types=1);

namespace App\Modules\Common\Database;

use Common\Domain\DomainEvent;

/**
 * Turns events into rows of the event store and back.
 *
 * The type is the name an event is stored under. It is part of the stored data, so it must not change when a class
 * is renamed or moved. The payload is stored as JSON, which keeps strings exactly but not the form of
 * numbers or the order of keys: numbers that must stay exact, such as amounts, are kept as strings.
 */
interface EventSerializer
{
    public function serialize(DomainEvent $event): SerializedEvent;

    /** @throws \UnexpectedValueException if the type is unknown or the payload does not describe an event of that type */
    public function deserialize(SerializedEvent $event): DomainEvent;
}
