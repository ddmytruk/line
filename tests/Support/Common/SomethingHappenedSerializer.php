<?php

declare(strict_types=1);

namespace Tests\Support\Common;

use App\Modules\Common\Database\EventSerializer;
use App\Modules\Common\Database\SerializedEvent;
use Common\Domain\DomainEvent;

/** Stores the test event, so that the database event store can be tested without business events. */
final readonly class SomethingHappenedSerializer implements EventSerializer
{
    public function serialize(DomainEvent $event): SerializedEvent
    {
        \assert($event instanceof SomethingHappened);

        return new SerializedEvent('something_happened', ['id' => $event->id, 'what' => $event->what]);
    }

    public function deserialize(SerializedEvent $event): DomainEvent
    {
        $id = $event->payload['id'] ?? null;
        $what = $event->payload['what'] ?? null;

        if ($event->type !== 'something_happened' || !\is_string($id) || !\is_string($what)) {
            throw new \UnexpectedValueException('Not a stored test event.');
        }

        return new SomethingHappened($id, $what);
    }
}
