<?php

declare(strict_types=1);

namespace Payroll\Domain\Event;

use Common\Domain\DomainEvent;
use Payroll\Domain\EarningLineId;
use Payroll\Domain\Money;

/** The payroll calculation sent a new value for an open line, which replaced the system value. */
final readonly class SystemValueRecalculated implements DomainEvent
{
    public function __construct(
        public EarningLineId $lineId,
        public Money $value,
        public \DateTimeImmutable $occurredAt,
    ) {}

    public function aggregateId(): string
    {
        return $this->lineId->value;
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
