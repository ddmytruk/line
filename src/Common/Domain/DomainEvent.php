<?php

declare(strict_types=1);

namespace Common\Domain;

/** A business fact that has happened. Immutable and named in the past tense. */
interface DomainEvent
{
    /** The aggregate the fact belongs to. */
    public function aggregateId(): string;

    public function occurredAt(): \DateTimeImmutable;
}
