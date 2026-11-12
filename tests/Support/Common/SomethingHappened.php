<?php

declare(strict_types=1);

namespace Tests\Support\Common;

use Common\Domain\DomainEvent;

/** A minimal event for testing the aggregate mechanics without business rules. */
final readonly class SomethingHappened implements DomainEvent
{
    public function __construct(
        public string $id,
        public string $what,
    ) {}

    public function aggregateId(): string
    {
        return $this->id;
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
    }
}
